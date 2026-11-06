<?php

namespace App\Livewire\Auth;

use App\Features\Auth\Actions\LoginUserAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;

    public function login(LoginUserAction $action)
    {
        $validated = $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $action->execute([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'remember' => $validated['remember'],
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
