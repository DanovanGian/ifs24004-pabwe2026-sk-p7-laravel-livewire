<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRepository implements StockRepositoryInterface
{
    public function queryFor(User $user): HasMany
    {
        return $user->stocks();
    }

    public function findFor(User $user, int $stockId): Stock
    {
        return $this->queryFor($user)->findOrFail($stockId);
    }

    public function createFor(User $user, array $data): Stock
    {
        return $this->queryFor($user)->create($data);
    }

    public function updateFor(User $user, Stock $stock, array $data): Stock
    {
        $ownedStock = $this->findFor($user, $stock->id);
        $ownedStock->update($data);

        return $ownedStock->refresh();
    }

    public function deleteFor(User $user, Stock $stock): void
    {
        $this->findFor($user, $stock->id)->delete();
    }
}
