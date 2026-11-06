<?php

namespace App\Features\Stock\Policies;

use App\Features\Stock\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'member'], true);
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->role === 'admin'
            || (int) $stock->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'member';
    }

    public function update(User $user, Stock $stock): bool
    {
        return $user->role === 'member'
            && (int) $stock->user_id === (int) $user->id;
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $user->role === 'member'
            && (int) $stock->user_id === (int) $user->id;
    }
}
