<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10263b">
    <title>{{ $title ?? 'Stockroom — Manajemen Inventaris' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <header>
        <nav class="nav" aria-label="Navigasi utama">
            @php($homeRoute = auth()->user()->role === 'admin' ? 'admin.users' : 'stocks.index')
            <a class="brand" href="{{ route($homeRoute) }}">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span class="brand-copy">Stockroom<small>INVENTORY CONTROL</small></span>
            </a>
            <div class="user-nav">
                <a class="nav-link" href="{{ route('profile') }}">Profil</a>
                <span class="user-identity">
                    <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="user-meta">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Member' }}</small>
                    </span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout" type="submit">Keluar</button>
                </form>
            </div>
        </nav>
    </header>
    <main class="container">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
