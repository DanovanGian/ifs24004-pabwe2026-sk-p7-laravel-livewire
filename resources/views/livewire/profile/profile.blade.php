<div class="profile-page">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Pengaturan akun</p>
            <h1>Edit profil</h1>
            <p class="muted">Perbarui informasi pribadi dan keamanan akun Anda.</p>
        </div>
    </div>

    @if (session('message'))
        <p class="flash" role="status">{{ session('message') }}</p>
    @endif

    <form wire:submit="save" class="profile-form">
        <section class="panel profile-identity">
            <div class="profile-avatar-wrap">
                @if ($profilePhoto)
                    <img class="profile-avatar" src="{{ $profilePhoto->temporaryUrl() }}" alt="Pratinjau foto profil">
                @elseif ($currentPhotoPath)
                    <img class="profile-avatar" src="{{ Storage::disk('public')->url($currentPhotoPath) }}" alt="Foto profil saat ini">
                @else
                    <span class="profile-avatar profile-avatar-placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($name ?: 'U', 0, 1)) }}</span>
                @endif
            </div>
            <div class="profile-identity-copy">
                <span class="profile-role">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Member' }}</span>
                <h2>{{ $name ?: 'Nama pengguna' }}</h2>
                <p class="muted">{{ $email ?: 'Email belum diisi' }}</p>
                <label class="upload-label" for="profilePhoto">Pilih foto profil</label>
                <input id="profilePhoto" class="profile-file-input" type="file" accept="image/jpeg,image/png,image/webp" wire:model="profilePhoto">
                <p class="profile-hint">JPG, PNG, atau WebP · Maksimal 2 MB</p>
                @error('profilePhoto') <p class="error">{{ $message }}</p> @enderror
                <span wire:loading wire:target="profilePhoto" class="profile-hint">Mengunggah foto...</span>
            </div>
        </section>

        <div class="profile-columns">
            <section class="panel profile-section">
                <div class="profile-section-heading">
                    <span class="profile-section-icon" aria-hidden="true">01</span>
                    <div><h2>Informasi pribadi</h2><p class="muted">Data yang digunakan pada akun Anda.</p></div>
                </div>

                <label for="name">Nama lengkap</label>
                <input id="name" type="text" wire:model="name" autocomplete="name" placeholder="Nama lengkap">
                @error('name') <p class="error">{{ $message }}</p> @enderror

                <label for="username">Username</label>
                <input id="username" type="text" wire:model="username" autocomplete="username" placeholder="Username">
                @error('username') <p class="error">{{ $message }}</p> @enderror

                <label for="email">Alamat email</label>
                <input id="email" type="email" wire:model="email" autocomplete="email" placeholder="nama@email.com">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </section>

            <section class="panel profile-section">
                <div class="profile-section-heading">
                    <span class="profile-section-icon" aria-hidden="true">02</span>
                    <div><h2>Keamanan</h2><p class="muted">Kosongkan jika tidak ingin mengubah password.</p></div>
                </div>

                <label for="currentPassword">Password saat ini</label>
                <input id="currentPassword" type="password" wire:model="currentPassword" autocomplete="current-password" placeholder="Masukkan password saat ini">
                @error('currentPassword') <p class="error">{{ $message }}</p> @enderror

                <label for="password">Password baru</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password" placeholder="Minimal 8 karakter">
                @error('password') <p class="error">{{ $message }}</p> @enderror

                <label for="password_confirmation">Konfirmasi password baru</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru">
            </section>
        </div>

        <div class="profile-actions">
            <p class="profile-hint">Perubahan profil hanya berlaku setelah disimpan.</p>
            <button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Simpan perubahan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
