<?php

namespace App\Livewire\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Adjust extends Component
{
    public int $stockId;
    public string $type = 'in';
    public int $quantity = 1;
    public string $note = '';

    public function mount(Stock $stock): void
    {
        $this->stockId = $this->findStock($stock->id)->id;
    }

    public function save()
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated) {
            $stock = $this->findStock($this->stockId, true);

            if ($validated['type'] === 'out' && $validated['quantity'] > $stock->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok keluar melebihi jumlah yang tersedia.',
                ]);
            }

            $newQuantity = $validated['type'] === 'in'
                ? $stock->quantity + $validated['quantity']
                : $stock->quantity - $validated['quantity'];

            $stock->update([
                'quantity' => $newQuantity,
                'status' => StockStatus::fromQuantity(
                    $newQuantity,
                    $stock->min_stock,
                )->value,
            ]);

            StockMovement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'note' => $validated['note'] ?: null,
            ]);
        });

        session()->flash('message', 'Perubahan stok berhasil disimpan.');

        return redirect()->route('dashboard');
    }

    private function findStock(int $id, bool $lock = false): Stock
    {
        $user = auth()->user();
        $query = $user->isAdmin()
            ? Stock::query()
            : $user->stocks();

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    public function render(): View
    {
        $stock = $this->findStock($this->stockId);

        return view('livewire.stock.adjust', [
            'stock' => $stock,
            'movements' => $stock->movements()->with('user')->latest()->limit(20)->get(),
        ]);
    }
}
