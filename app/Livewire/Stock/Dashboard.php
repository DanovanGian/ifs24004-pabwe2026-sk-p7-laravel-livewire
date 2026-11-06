<?php

namespace App\Livewire\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        $stock = $user->isAdmin()
            ? Stock::query()->findOrFail($id)
            : $user->stocks()->findOrFail($id);

        $imagePath = $stock->cover_image_path;
        $stock->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        session()->flash('message', 'Barang berhasil dihapus.');
    }

    public function export()
    {
        $stocks = $this->filteredStocksQuery()->get();

        return response()->streamDownload(function () use ($stocks) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Nama barang', 'SKU', 'Deskripsi', 'Jumlah',
                'Satuan', 'Harga', 'Batas minimum', 'Status',
            ]);

            foreach ($stocks as $stock) {
                $status = StockStatus::tryFrom($stock->status);

                fputcsv($output, [
                    $stock->name,
                    $stock->sku,
                    $stock->description,
                    $stock->quantity,
                    $stock->unit,
                    $stock->price,
                    $stock->min_stock,
                    $status?->label() ?? $stock->status,
                ]);
            }

            fclose($output);
        }, 'stok-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredStocksQuery(): Builder|HasMany
    {
        $user = auth()->user();
        $query = $user->isAdmin() ? Stock::query() : $user->stocks();

        return $query
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', fn ($query) =>
                $query->where('status', $this->statusFilter)
            );
    }

    public function render(): View
    {
        $user = auth()->user();
        $baseQuery = $user->isAdmin() ? Stock::query() : $user->stocks();

        return view('livewire.stock.dashboard', [
            'stocks' => $this->filteredStocksQuery()->latest()->paginate(10),
            'totalProducts' => (clone $baseQuery)->count(),
            'totalQuantity' => (clone $baseQuery)->sum('quantity'),
            'lowStockCount' => (clone $baseQuery)->where('status', 'low_stock')->count(),
            'outOfStockCount' => (clone $baseQuery)->where('status', 'out_of_stock')->count(),
        ]);
    }
}
