<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class StockList extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showFormModal = false;

    public ?int $editingStockId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int $quantity = 0;

    public string $unit = 'pcs';

    public string $price = '';

    public int $min_stock = 5;

    public $coverImage;

    public ?string $currentImagePath = null;

    public $excelFile;

    public string $aiPrompt = '';

    public int $aiCount = 5;

    public array $generatedItems = [];

    public array $selectedAiItems = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Stock::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $stock = auth()->user()->stocks()->findOrFail($id);
        Gate::authorize('update', $stock);

        $this->resetValidation();
        $this->editingStockId = $stock->id;
        $this->name = $stock->name;
        $this->sku = $stock->sku ?? '';
        $this->description = $stock->description ?? '';
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price ?? '';
        $this->min_stock = $stock->min_stock;
        $this->coverImage = null;
        $this->currentImagePath = $stock->cover_image_path;
        $this->showFormModal = true;
    }

    public function closeForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(StockService $stockService): void
    {
        $stock = $this->editingStockId
            ? auth()->user()->stocks()->findOrFail($this->editingStockId)
            : null;

        Gate::authorize($stock ? 'update' : 'create', $stock ?? Stock::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stocks', 'sku')->ignore($this->editingStockId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:'.Stock::MAX_PRICE],
            'min_stock' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'coverImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldImagePath = $stock?->cover_image_path;
        $newImagePath = isset($validated['coverImage'])
            ? $this->coverImage->store('stocks', 'public')
            : $oldImagePath;
        unset($validated['coverImage']);

        $validated['sku'] = filled($validated['sku'] ?? null)
            ? trim($validated['sku'])
            : null;
        $validated['price'] = filled($validated['price'] ?? null)
            ? $validated['price']
            : null;
        $validated['status'] = StockStatus::fromQuantity(
            (int) $validated['quantity'],
            (int) $validated['min_stock'],
        )->value;
        $validated['cover_image_path'] = $newImagePath;

        if ($stock) {
            $stockService->updateFor(auth()->user(), $stock, $validated);
        } else {
            $stockService->createFor(auth()->user(), $validated);
        }

        if ($newImagePath !== $oldImagePath && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        session()->flash('message', $stock
            ? 'Barang berhasil diperbarui.'
            : 'Barang berhasil ditambahkan.');

        $this->closeForm();
    }

    public function delete(int $id, StockService $stockService): void
    {
        $stock = auth()->user()->stocks()->findOrFail($id);
        $imagePath = $stock->cover_image_path;
        $stockService->deleteFor(auth()->user(), $stock);

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        session()->flash('message', 'Barang berhasil dihapus.');
        $this->resetPage();
    }

    public function import(StockExcelService $excelService): void
    {
        $validated = $this->validate([
            'excelFile' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $imported = $excelService->importFor(auth()->user(), $validated['excelFile']);
        $this->excelFile = null;
        $this->resetPage();
        session()->flash('message', $imported.' barang berhasil diimpor dari Excel.');
    }

    public function generateWithAi(AiClientInterface $aiClient): void
    {
        Gate::authorize('create', Stock::class);
        $this->validate([
            'aiPrompt' => ['required', 'string', 'max:1000'],
            'aiCount' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $this->generatedItems = $aiClient->generateStockItems($this->aiPrompt, $this->aiCount);
        } catch (\RuntimeException $exception) {
            $this->addError('aiPrompt', $exception->getMessage());
            $this->generatedItems = [];

            return;
        }

        $this->selectedAiItems = array_keys($this->generatedItems);
    }

    public function saveGeneratedItems(StockService $stockService): void
    {
        Gate::authorize('create', Stock::class);
        if ($this->generatedItems === [] || $this->selectedAiItems === []) {
            throw ValidationException::withMessages([
                'selectedAiItems' => 'Pilih minimal satu item dari hasil AI.',
            ]);
        }

        $validatedItems = [];

        foreach ($this->selectedAiItems as $index) {
            if (! is_numeric($index) || ! array_key_exists((int) $index, $this->generatedItems)) {
                throw ValidationException::withMessages([
                    'selectedAiItems' => 'Pilihan item AI tidak valid.',
                ]);
            }

            try {
                $validatedItems[] = $this->validateGeneratedItem($this->generatedItems[(int) $index]);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    'selectedAiItems' => 'Draf AI berisi nilai di luar batas: '.$exception->validator->errors()->first(),
                ]);
            }
        }

        foreach ($validatedItems as $item) {
            $stockService->createFor(auth()->user(), $item);
        }

        $savedCount = count($validatedItems);
        $this->generatedItems = [];
        $this->selectedAiItems = [];
        $this->aiPrompt = '';
        $this->resetPage();
        session()->flash('message', $savedCount.' item AI berhasil disimpan.');
    }

    private function validateGeneratedItem(array $item): array
    {
        $validated = validator($item, [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:stocks,sku'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:'.Stock::MAX_PRICE],
            'min_stock' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
        ])->validate();

        return $validated;
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingStockId = null;
        $this->name = '';
        $this->sku = '';
        $this->description = '';
        $this->quantity = 0;
        $this->unit = 'pcs';
        $this->price = '';
        $this->min_stock = 5;
        $this->coverImage = null;
        $this->currentImagePath = null;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Stock::class);

        $baseQuery = auth()->user()->stocks();
        $stocks = (clone $baseQuery)
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter)
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.stock.stock-list', [
            'stocks' => $stocks,
            'totalProducts' => (clone $baseQuery)->count(),
            'totalQuantity' => (clone $baseQuery)->sum('quantity'),
            'lowStockCount' => (clone $baseQuery)->where('status', 'low_stock')->count(),
            'outOfStockCount' => (clone $baseQuery)->where('status', 'out_of_stock')->count(),
        ]);
    }
}
