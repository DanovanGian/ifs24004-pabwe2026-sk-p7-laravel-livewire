<div>
    <h1>Buat akun</h1>
    <p class="muted">Daftar sebagai anggota Stock Management System.</p>

    <form wire:submit="register">
        <label for="name">Nama</label>
        <input id="name" type="text" wire:model="name" autocomplete="name">
        @error('name') <p class="error">{{ $message }}</p> @enderror

        <label for="username">Username</label>
        <input id="username" type="text" wire:model="username" autocomplete="username">
        @error('username') <p class="error">{{ $message }}</p> @enderror

        <label for="email">Email</label>
        <input id="email" type="email" wire:model="email" autocomplete="email">
        @error('email') <p class="error">{{ $message }}</p> @enderror

        <label for="password">Password</label>
        <input id="password" type="password" wire:model="password" autocomplete="new-password">
        @error('password') <p class="error">{{ $message }}</p> @enderror

        <label for="password_confirmation">Konfirmasi password</label>
        <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password">

        <button type="submit" wire:loading.attr="disabled">Daftar</button>
    </form>

    <p class="link">Sudah punya akun? <a href="/login">Masuk</a></p>
</div>
