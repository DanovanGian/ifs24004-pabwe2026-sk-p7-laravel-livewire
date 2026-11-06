<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Adjust extends Component
{
    public int $stockId;

    public string $type = 'in';

    public int $quantity = 1;

    public string $note = '';

    public function mount(Stock $stock): void
    {
        Gate::authorize('update', $stock);
        $this->stockId = $stock->id;
    }

    public function save(StockService $stockService)
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $stock = auth()->user()->stocks()->findOrFail($this->stockId);
        $stockService->adjust(
            auth()->user(),
            $stock,
            $validated['type'],
            (int) $validated['quantity'],
            $validated['note'] ?: null,
        );

        session()->flash('message', 'Perubahan stok berhasil disimpan.');

        return redirect()->route('stocks.index');
    }

    public function render(): View
    {
        $stock = auth()->user()->stocks()->findOrFail($this->stockId);
        Gate::authorize('view', $stock);

        return view('livewire.stock.adjust', [
            'stock' => $stock,
            'movements' => $stock->movements()->with('user')->latest()->limit(20)->get(),
        ]);
    }
}
