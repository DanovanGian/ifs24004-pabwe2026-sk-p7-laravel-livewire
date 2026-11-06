<div>
    <p><a href="{{ route('stocks.index') }}">← Kembali ke daftar stok</a></p>

    <section class="panel">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap">
            <div>
                <h1>{{ $stock->name }}</h1>
                <p class="muted">SKU: {{ $stock->sku ?? '—' }}</p>
                @php($status = \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status))
                <span class="badge {{ $stock->status }}">{{ $status?->label() ?? $stock->status }}</span>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('stocks.edit', $stock) }}">Edit barang</a>
                <a href="{{ route('stocks.adjust', $stock) }}">Atur stok</a>
            </div>
        </div>

        @if ($stock->cover_image_path)
            <p><img src="{{ Storage::disk('public')->url($stock->cover_image_path) }}" alt="Foto {{ $stock->name }}" style="max-width:320px;max-height:260px;object-fit:cover;border-radius:12px"></p>
        @endif

        <div class="cards">
            <article class="card"><p>Jumlah</p><strong>{{ $stock->quantity }} {{ $stock->unit }}</strong></article>
            <article class="card"><p>Harga</p><strong>{{ $stock->price === null ? '—' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</strong></article>
            <article class="card"><p>Batas minimum</p><strong>{{ $stock->min_stock }} {{ $stock->unit }}</strong></article>
        </div>

        <h2>Deskripsi</h2>
        <div id="stock-description-viewer" wire:ignore></div>
    </section>

    <section class="panel" style="margin-top:20px">
        <h2>Riwayat stok</h2>
        @if ($movements->isEmpty())
            <p class="muted">Belum ada pergerakan stok.</p>
        @else
            <table>
                <thead><tr><th>Waktu</th><th>Jenis</th><th>Jumlah</th><th>Pengguna</th><th>Catatan</th></tr></thead>
                <tbody>
                    @foreach ($movements as $movement)
                        <tr wire:key="movement-{{ $movement->id }}">
                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->type === 'in' ? 'Masuk' : 'Keluar' }}</td>
                            <td>{{ $movement->quantity }} {{ $stock->unit }}</td>
                            <td>{{ $movement->user->name }}</td>
                            <td>{{ $movement->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>

@script
<script>
    new window.StockMarkdownViewer({
        el: document.querySelector('#stock-description-viewer'),
        initialValue: @js($stock->description ?? ''),
        usageStatistics: false,
    });
</script>
@endscript
