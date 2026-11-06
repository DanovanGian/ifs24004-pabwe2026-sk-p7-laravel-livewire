<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UserList extends Component
{
    use WithPagination;

    public string $search = '';

    protected AdminUserService $adminUserService;

    public function boot(AdminUserService $adminUserService): void
    {
        $this->adminUserService = $adminUserService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.user-list', [
            'users' => $this->adminUserService->listUsers($this->search),
        ]);
    }
}
