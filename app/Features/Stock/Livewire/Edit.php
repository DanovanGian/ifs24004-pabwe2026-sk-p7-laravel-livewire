<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public int $stockId;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int $quantity = 0;

    public string $unit = 'pcs';

    public string $price = '';

    public int $min_stock = 5;

    public $coverImage;

    public ?string $currentImagePath = null;

    public function mount(Stock $stock): void
    {
        $record = $this->findStock($stock->id);

        $this->stockId = $record->id;
        $this->name = $record->name;
        $this->sku = $record->sku ?? '';
        $this->description = $record->description ?? '';
        $this->quantity = $record->quantity;
        $this->unit = $record->unit;
        $this->price = $record->price ?? '';
        $this->min_stock = $record->min_stock;
        $this->currentImagePath = $record->cover_image_path;
    }

    public function save()
    {
        $stock = $this->findStock($this->stockId);
        Gate::authorize('update', $stock);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stocks', 'sku')->ignore($this->stockId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:'.Stock::MAX_PRICE],
            'min_stock' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'coverImage' => ['nullable', 'image', 'max:2048'],
        ]);

        $oldImagePath = $stock->cover_image_path;
        $newImagePath = isset($validated['coverImage'])
            ? $this->coverImage->store('stocks', 'public')
            : $oldImagePath;
        unset($validated['coverImage']);

        $stock->update([
            ...$validated,
            'sku' => $validated['sku'] ?: null,
            'price' => filled($validated['price'] ?? null) ? $validated['price'] : null,
            'cover_image_path' => $newImagePath,
            'status' => StockStatus::fromQuantity(
                (int) $validated['quantity'],
                (int) $validated['min_stock'],
            )->value,
        ]);

        if ($newImagePath !== $oldImagePath && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        session()->flash('message', 'Barang berhasil diperbarui.');

        return redirect()->route('stocks.index');
    }

    private function findStock(int $id): Stock
    {
        $stock = Stock::query()->findOrFail($id);
        Gate::authorize('update', $stock);

        return $stock;
    }

    public function render(): View
    {
        return view('livewire.stock.edit');
    }
}
