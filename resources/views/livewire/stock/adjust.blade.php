<div class="panel">
    <p><a href="{{ route('dashboard') }}">← Kembali ke dashboard</a></p>
    <h1>Atur stok: {{ $stock->name }}</h1>
    <p class="muted">Stok tersedia: <strong>{{ $stock->quantity }} {{ $stock->unit }}</strong></p>

    <form wire:submit="save">
        <label for="type">Jenis pergerakan</label>
        <select id="type" wire:model="type">
            <option value="in">Stok masuk</option>
            <option value="out">Stok keluar</option>
        </select>
        @error('type') <p class="error">{{ $message }}</p> @enderror

        <label for="quantity">Jumlah</label>
        <input id="quantity" type="number" min="1" wire:model="quantity">
        @error('quantity') <p class="error">{{ $message }}</p> @enderror

        <label for="note">Catatan (opsional)</label>
        <textarea id="note" rows="3" wire:model="note"></textarea>
        @error('note') <p class="error">{{ $message }}</p> @enderror

        <button type="submit" wire:loading.attr="disabled">Simpan perubahan stok</button>
    </form>

    <h2 style="margin-top:32px">Riwayat pergerakan terakhir</h2>
    @if ($movements->isEmpty())
        <p class="muted">Belum ada riwayat pergerakan stok.</p>
    @else
        <table>
            <thead>
                <tr><th>Waktu</th><th>Jenis</th><th>Jumlah</th><th>Pengguna</th><th>Catatan</th></tr>
            </thead>
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
</div>
