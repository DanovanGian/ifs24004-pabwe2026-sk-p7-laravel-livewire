<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(private StockRepositoryInterface $stocks) {}

    public function createFor(User $user, array $data): Stock
    {
        Gate::forUser($user)->authorize('create', Stock::class);
        $data = $this->normalizeStockData($data);

        return $this->stocks->createFor($user, $data);
    }

    public function updateFor(User $user, Stock $stock, array $data): Stock
    {
        Gate::forUser($user)->authorize('update', $stock);
        $data = $this->normalizeStockData($data);

        return $this->stocks->updateFor($user, $stock, $data);
    }

    public function deleteFor(User $user, Stock $stock): void
    {
        Gate::forUser($user)->authorize('delete', $stock);
        $this->stocks->deleteFor($user, $stock);
    }

    public function adjust(User $user, Stock $stock, string $type, int $quantity, ?string $note = null): Stock
    {
        Gate::forUser($user)->authorize('update', $stock);

        if (! in_array($type, ['in', 'out'], true) || $quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jenis dan jumlah penyesuaian stok tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($user, $stock, $type, $quantity, $note): Stock {
            $lockedStock = $this->stocks->queryFor($user)
                ->lockForUpdate()
                ->findOrFail($stock->id);

            if ($type === 'out' && $quantity > $lockedStock->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok keluar melebihi jumlah yang tersedia.',
                ]);
            }

            if ($type === 'in' && $quantity > Stock::MAX_QUANTITY - $lockedStock->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah stok setelah penyesuaian melebihi batas yang didukung.',
                ]);
            }

            $newQuantity = $type === 'in'
                ? $lockedStock->quantity + $quantity
                : $lockedStock->quantity - $quantity;

            $lockedStock->update([
                'quantity' => $newQuantity,
                'status' => StockStatus::fromQuantity($newQuantity, $lockedStock->min_stock)->value,
            ]);

            StockMovement::create([
                'stock_id' => $lockedStock->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note,
            ]);

            return $lockedStock->refresh();
        });
    }

    private function normalizeStockData(array $data): array
    {
        $data['sku'] = filled($data['sku'] ?? null) ? trim($data['sku']) : null;
        $data['price'] = filled($data['price'] ?? null) ? $data['price'] : null;
        $data['status'] = StockStatus::fromQuantity(
            (int) $data['quantity'],
            (int) $data['min_stock'],
        )->value;

        return $data;
    }
}
