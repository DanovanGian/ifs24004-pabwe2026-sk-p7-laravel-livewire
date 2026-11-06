<?php

namespace App\Features\Profile\Livewire;

use App\Features\Profile\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $profilePhoto;

    public ?string $currentPhotoPath = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->currentPhotoPath = $user->profile_photo_path;
    }

    public function save(ProfileService $profileService): void
    {
        $user = auth()->user();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'currentPassword' => [
                Rule::requiredIf(filled($this->password)),
                'current_password',
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'profilePhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $profileService->update(
            $user,
            $validated,
            $this->profilePhoto,
        );

        $this->currentPhotoPath = auth()->user()->profile_photo_path;
        $this->profilePhoto = null;
        $this->currentPassword = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
        session()->flash('message', 'Profil berhasil diperbarui.');
    }

    public function render(): View
    {
        return view('livewire.profile.profile');
    }
}
