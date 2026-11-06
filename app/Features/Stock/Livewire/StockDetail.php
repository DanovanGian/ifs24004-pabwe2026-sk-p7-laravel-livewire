<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StockDetail extends Component
{
    public int $stockId;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);
        $this->stockId = $stock->id;
    }

    public function render(): View
    {
        $stock = Stock::query()
            ->with(['user', 'movements.user'])
            ->findOrFail($this->stockId);

        Gate::authorize('view', $stock);

        return view('livewire.stock.stock-detail', [
            'stock' => $stock,
            'movements' => $stock->movements()->with('user')->latest()->limit(20)->get(),
        ]);
    }
}
