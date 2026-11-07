<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int $quantity = 0;

    public string $unit = 'pcs';

    public string $price = '';

    public int $min_stock = 5;

    public $coverImage;

    public function save()
    {
        Gate::authorize('create', Stock::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:stocks,sku'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:'.Stock::MAX_PRICE],
            'min_stock' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'coverImage' => ['nullable', 'image', 'max:2048'],
        ]);

        $coverImagePath = isset($validated['coverImage'])
            ? $this->coverImage->store('stocks', 'public')
            : null;
        unset($validated['coverImage']);

        $validated['sku'] = filled($validated['sku'] ?? null)
            ? trim($validated['sku'])
            : null;
        $validated['price'] = filled($validated['price'] ?? null)
            ? $validated['price']
            : null;

        Stock::create([
            ...$validated,
            'user_id' => auth()->id(),
            'cover_image_path' => $coverImagePath,
            'status' => StockStatus::fromQuantity(
                (int) $validated['quantity'],
                (int) $validated['min_stock'],
            )->value,
        ]);

        session()->flash('message', 'Barang berhasil ditambahkan.');

        return redirect()->route('stocks.index');
    }

    public function render(): View
    {
        return view('livewire.stock.create');
    }
}
