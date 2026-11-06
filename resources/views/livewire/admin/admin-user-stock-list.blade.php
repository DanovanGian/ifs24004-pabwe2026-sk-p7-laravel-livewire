<div>
    <p><a href="{{ route('admin.users') }}">← Kembali ke daftar member</a></p>
    <h1>Stok milik {{ $user->name }}</h1>
    <p class="muted">Halaman ini hanya-baca.</p>

    <section class="panel">
        @if ($stocks->isEmpty())
            <p class="muted">Member ini belum memiliki stok.</p>
        @else
            <table>
                <thead>
                    <tr><th>Nama barang</th><th>SKU</th><th>Jumlah</th><th>Harga</th><th>Batas minimum</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($stocks as $stock)
                        @php($status = \App\Features\Stock\Enums\StockStatus::tryFrom($stock->status))
                        <tr wire:key="admin-stock-{{ $stock->id }}">
                            <td>{{ $stock->name }}</td>
                            <td>{{ $stock->sku ?? '—' }}</td>
                            <td>{{ $stock->quantity }} {{ $stock->unit }}</td>
                            <td>{{ $stock->price === null ? '—' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</td>
                            <td>{{ $stock->min_stock }} {{ $stock->unit }}</td>
                            <td>{{ $status?->label() ?? $stock->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">{{ $stocks->links() }}</div>
        @endif
    </section>
</div>
