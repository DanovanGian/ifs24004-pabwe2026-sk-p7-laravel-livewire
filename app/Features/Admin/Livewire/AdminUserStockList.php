<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class AdminUserStockList extends Component
{
    use WithPagination;

    public int $userId;

    protected AdminUserService $adminUserService;

    public function boot(AdminUserService $adminUserService): void
    {
        $this->adminUserService = $adminUserService;
    }

    public function mount(User $user): void
    {
        abort_unless($user->role === 'member', 404);
        $this->userId = $user->id;
    }

    public function render(): View
    {
        $user = User::query()->where('role', 'member')->findOrFail($this->userId);

        return view('livewire.admin.admin-user-stock-list', [
            'user' => $user,
            'stocks' => $this->adminUserService->stocksFor($user),
        ]);
    }
}
