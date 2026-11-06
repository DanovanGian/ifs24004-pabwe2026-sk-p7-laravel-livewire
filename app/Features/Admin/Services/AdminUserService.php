<?php

namespace App\Features\Admin\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function listUsers(string $search = ''): LengthAwarePaginator
    {
        return User::query()
            ->where('role', 'member')
            ->withCount('stocks')
            ->when(trim($search) !== '', function ($query) use ($search): void {
                $term = '%'.trim($search).'%';
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);
    }

    public function stocksFor(User $user): LengthAwarePaginator
    {
        return $user->stocks()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);
    }
}
