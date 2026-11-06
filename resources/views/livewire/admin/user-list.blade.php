<div>
    <h1>Daftar Member</h1>
    <p class="muted">Ringkasan akun dan jumlah stok. Data stok hanya dapat dilihat admin.</p>

    <label for="user-search">Cari member</label>
    <input id="user-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, username, atau email">

    <section class="panel" style="margin-top:20px">
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
