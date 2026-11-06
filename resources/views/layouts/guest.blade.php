<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Stock Management System' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            padding: 32px;
            border-radius: 16px;
            background: white;
            box-shadow: 0 12px 36px rgb(15 23 42 / 10%);
        }
        h1 { margin: 0 0 8px; font-size: 26px; }
        .muted { margin: 0 0 24px; color: #64748b; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font: inherit;
        }
        input:focus { outline: 2px solid #93c5fd; border-color: #2563eb; }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        button:disabled { opacity: .65; cursor: wait; }
        .error { margin: 6px 0 0; color: #b91c1c; font-size: 14px; }
        .link { margin: 20px 0 0; text-align: center; color: #475569; }
        .link a { color: #2563eb; font-weight: 600; }
    </style>
    @livewireStyles
</head>
<body>
    <main class="auth-card">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
