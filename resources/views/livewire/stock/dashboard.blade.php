<div>
    <h1>Dashboard</h1>
    <p class="muted">Ringkasan persediaan barang.</p>

    @if (session('message'))
        <p class="flash">{{ session('message') }}</p>
    @endif

    <p>
        <a href="{{ route('stocks.create') }}" style="display:inline-block;padding:10px 14px;border-radius:8px;background:#2563eb;color:white;text-decoration:none">
            + Tambah barang
        </a>
        <button type="button" wire:click="export">Ekspor CSV</button>
    </p>

    <section class="cards">
        <article class="card"><p>Jenis barang</p><strong>{{ $totalProducts }}</strong></article>
        <article class="card"><p>Total unit stok</p><strong>{{ $totalQuantity }}</strong></article>
        <article class="card"><p>Stok menipis</p><strong>{{ $lowStockCount }}</strong></article>
        <article class="card"><p>Stok habis</p><strong>{{ $outOfStockCount }}</strong></article>
    </section>

    <section class="panel">
        <h2>Daftar stok</h2>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px">
            <input type="search" placeholder="Cari nama atau SKU..."
                   wire:model.live.debounce.300ms="search"
                   style="width:auto;min-width:240px">
            <select wire:model.live="statusFilter" style="width:auto;min-width:180px">
                <option value="">Semua status</option>
                <option value="available">Tersedia</option>
                <option value="low_stock">Stok menipis</option>
                <option value="out_of_stock">Habis</option>
            </select>
        </div>

        @if ($stocks->isEmpty())
            <p class="muted">Tidak ada barang yang cocok.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Foto</th><th>Nama barang</th><th>SKU</th>
                        <th>Jumlah</th><th>Harga</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stocks as $stock)
                        @php($status = \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status))
                        <tr wire:key="stock-{{ $stock->id }}">
                            <td>
                                @if ($stock->cover_image_path)
                                    <img class="thumb" src="{{ Storage::disk('public')->url($stock->cover_image_path) }}" alt="Foto {{ $stock->name }}">
                                @else
                                    <span class="thumb" style="display:inline-block"></span>
                                @endif
                            </td>
                            <td>{{ $stock->name }}</td>
                            <td>{{ $stock->sku ?? '—' }}</td>
                            <td>{{ $stock->quantity }} {{ $stock->unit }}</td>
                            <td>{{ $stock->price === null ? '—' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $stock->status }}">{{ $status?->label() ?? $stock->status }}</span></td>
                            <td>
                                <a href="{{ route('stocks.edit', $stock) }}">Edit</a>
                                <a href="{{ route('stocks.adjust', $stock) }}">Atur stok</a>
                                <button type="button" wire:click="delete({{ $stock->id }})"
                                        wire:confirm="Yakin ingin menghapus barang ini?">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">{{ $stocks->links() }}</div>
        @endif
    </section>
</div>
