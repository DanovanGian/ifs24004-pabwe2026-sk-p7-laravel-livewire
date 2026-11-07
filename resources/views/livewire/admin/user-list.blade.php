<div>
    <div class="page-heading">
        <div><p class="eyebrow">Kontrol administrator</p><h1>Daftar member</h1><p class="muted">Ringkasan akun dan inventaris. Data stok bersifat hanya baca.</p></div>
    </div>

    <label for="user-search">Cari member</label>
    <input id="user-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, username, atau email">

    <section class="panel stock-table-panel" style="margin-top:20px">
        <div class="section-heading"><div><h2>Akun member</h2></div><span class="table-count">{{ $users->total() }} akun</span></div>
        <table>
            <thead>
                <tr><th>Nama</th><th>Username</th><th>Email</th><th>Jenis stok</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="member-{{ $user->id }}">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->stocks_count }}</td>
                        <td><a href="{{ route('admin.users.stocks', $user) }}">Lihat stok</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Member tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $users->links() }}</div>
    </section>
</div>
