<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface StockRepositoryInterface
{
    public function queryFor(User $user): HasMany;

    public function findFor(User $user, int $stockId): Stock;

    public function createFor(User $user, array $data): Stock;

    public function updateFor(User $user, Stock $stock, array $data): Stock;

    public function deleteFor(User $user, Stock $stock): void;
}
