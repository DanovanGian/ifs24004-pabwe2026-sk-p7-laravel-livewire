<div>
    <h1>Masuk</h1>
    <p class="muted">Masuk ke Stock Management System.</p>

    <form wire:submit="login">
        <label for="login-username">Username</label>
        <input id="login-username" type="text" wire:model="username" autocomplete="username" autofocus>
        @error('username') <p class="error">{{ $message }}</p> @enderror

        <label for="login-password">Password</label>
        <input id="login-password" type="password" wire:model="password" autocomplete="current-password">
        @error('password') <p class="error">{{ $message }}</p> @enderror

        <label>
            <input type="checkbox" wire:model="remember" style="width:auto">
            Ingat saya
        </label>

        <button id="login-submit" type="submit" wire:loading.attr="disabled">Masuk</button>
    </form>

    <p class="link">Belum punya akun? <a href="/register">Daftar</a></p>
</div>
