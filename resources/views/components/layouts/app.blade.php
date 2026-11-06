<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Management System</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; }
        header { background: #0f172a; color: white; }
        .nav, .container { width: min(1100px, calc(100% - 32px)); margin: auto; }
        .nav { min-height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .brand { color: white; font-weight: 700; text-decoration: none; }
        .user-nav { display: flex; align-items: center; gap: 16px; color: #cbd5e1; font-size: 14px; }
        .logout { padding: 8px 12px; border: 1px solid #64748b; border-radius: 7px; color: white; background: transparent; cursor: pointer; }
        .container { padding: 32px 0; }
        .muted { color: #64748b; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin: 24px 0; }
        .card, .panel { padding: 20px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; }
        .card p { margin: 0; color: #64748b; }
        .card strong { display: block; margin-top: 10px; font-size: 28px; }
        .panel { overflow-x: auto; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input:not([type="checkbox"]), textarea, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: white; color: #0f172a; font: inherit; }
        input[type="file"] { padding: 8px; }
        input:focus, textarea:focus, select:focus { outline: 2px solid #bfdbfe; border-color: #2563eb; }
        textarea { resize: vertical; }
        form > button, td button { padding: 9px 12px; border: 0; border-radius: 7px; background: #2563eb; color: white; font: inherit; cursor: pointer; }
        form > button { margin-top: 20px; }
        td button { background: #dc2626; }
        button:disabled { opacity: .6; cursor: wait; }
        .error { color: #b91c1c; font-size: 14px; margin: 5px 0 0; }
        .flash { margin: 14px 0; padding: 12px; border-radius: 8px; background: #dcfce7; color: #166534; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 13px 10px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        th { color: #64748b; font-size: 13px; }
        .badge { padding: 5px 9px; border-radius: 999px; font-size: 12px; }
        .available { color: #15803d; background: #dcfce7; }
        .low_stock { color: #a16207; background: #fef9c3; }
        .out_of_stock { color: #b91c1c; background: #fee2e2; }
        .thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 7px; background: #e2e8f0; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 18px; }
        .pagination a, .pagination span { padding: 8px 11px; border: 1px solid #cbd5e1; border-radius: 7px; text-decoration: none; color: #334155; }
        .modal-backdrop { position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; padding: 20px; background: rgb(15 23 42 / 60%); }
        .modal-panel { position: relative; width: min(720px, 100%); max-height: 92vh; overflow-y: auto; padding: 24px; border-radius: 14px; background: white; box-shadow: 0 24px 70px rgb(15 23 42 / 25%); }
        .modal-close { position: absolute; top: 12px; right: 14px; border: 0; background: transparent; color: #475569; font-size: 28px; cursor: pointer; }
        @media (max-width: 600px) { .container { padding-top: 22px; } .user-nav span { display: none; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <header>
        <nav class="nav">
            @php($homeRoute = auth()->user()->role === 'admin' ? 'admin.users' : 'stocks.index')
            <a class="brand" href="{{ route($homeRoute) }}">Stock Management System</a>
            <div class="user-nav">
                <a href="{{ route('profile') }}" style="color:#e2e8f0">Profil</a>
                <span>{{ auth()->user()->name }} · {{ auth()->user()->role }}</span>
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
