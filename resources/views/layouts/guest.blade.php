<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10263b">
    <title>{{ $title ?? 'Masuk — Stockroom' }}</title>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="auth-body">
    <div class="auth-wrap">
        <section class="auth-form-side">
            <a class="auth-brand" href="/">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span>Stockroom<small>INVENTORY CONTROL</small></span>
            </a>
            <main class="auth-card">
                {{ $slot }}
            </main>
            <p class="auth-footnote">© {{ date('Y') }} Stockroom · Kelola inventaris dengan lebih mudah</p>
        </section>

        <aside class="auth-visual">
            <div class="visual-orb visual-orb-one"></div>
            <div class="visual-orb visual-orb-two"></div>
            <div class="visual-content">
                <span class="visual-kicker"><i></i> INVENTARIS DALAM KENDALI</span>
                <h2>Semua stok.<br><em>Satu tempat.</em></h2>
                <p>Pantau persediaan, catat perubahan, dan jaga kebutuhan barang tetap teratur.</p>
                <div class="visual-board" aria-hidden="true">
                    <div class="board-top"><span>Ringkasan stok</span><b>•••</b></div>
                    <div class="board-total"><strong>1.248</strong><span>unit terpantau</span></div>
                    <div class="board-chart"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                    <div class="board-legend"><span><i></i> Persediaan terjaga</span><b>+12,8%</b></div>
                </div>
            </div>
            <span class="visual-corner">STOCKROOM / 01</span>
        </aside>
    </div>
    @livewireScripts
</body>
</html>
