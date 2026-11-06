<div class="panel" style="max-width:760px;margin:auto">
    <h1>Profil</h1>
    <p class="muted">Ubah informasi akun, password, dan foto profil.</p>

    @if (session('message'))
        <p class="flash">{{ session('message') }}</p>
    @endif

    <form wire:submit="save">
        @if ($profilePhoto)
            <p><img src="{{ $profilePhoto->temporaryUrl() }}" alt="Pratinjau foto profil" style="width:96px;height:96px;object-fit:cover;border-radius:50%"></p>
        @elseif ($currentPhotoPath)
            <p><img src="{{ Storage::disk('public')->url($currentPhotoPath) }}" alt="Foto profil" style="width:96px;height:96px;object-fit:cover;border-radius:50%"></p>
        @endif
        <label for="profilePhoto">Foto profil (maksimal 2 MB)</label>
        <input id="profilePhoto" type="file" accept="image/jpeg,image/png,image/webp" wire:model="profilePhoto">
        @error('profilePhoto') <p class="error">{{ $message }}</p> @enderror

        <label for="name">Nama</label>
        <input id="name" type="text" wire:model="name">
        @error('name') <p class="error">{{ $message }}</p> @enderror

        <label for="username">Username</label>
        <input id="username" type="text" wire:model="username">
        @error('username') <p class="error">{{ $message }}</p> @enderror

        <label for="email">Email</label>
        <input id="email" type="email" wire:model="email">
        @error('email') <p class="error">{{ $message }}</p> @enderror

        <h2>Ubah password (opsional)</h2>
        <label for="currentPassword">Password saat ini</label>
        <input id="currentPassword" type="password" wire:model="currentPassword" autocomplete="current-password">
        @error('currentPassword') <p class="error">{{ $message }}</p> @enderror

        <label for="password">Password baru</label>
        <input id="password" type="password" wire:model="password" autocomplete="new-password">
        @error('password') <p class="error">{{ $message }}</p> @enderror

        <label for="password_confirmation">Konfirmasi password baru</label>
        <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password">

        <button type="submit" wire:loading.attr="disabled">Simpan profil</button>
    </form>
</div>
