<div wire:keydown.escape.window="closeForm">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Ringkasan inventaris</p>
            <h1>Daftar stok</h1>
            <p class="muted">Pantau barang, jumlah persediaan, dan kondisi stok Anda.</p>
        </div>
        <div class="page-actions">
            <button class="btn-outline" type="button" wire:click="openCreate">＋ Tambah barang</button>
            <a class="btn-soft" href="{{ route('stocks.export', array_filter(['search' => $search, 'status' => $statusFilter], fn ($value) => $value !== '')) }}">Unduh Excel</a>
        </div>
    </div>

    @if (session('message'))
        <p class="flash">{{ session('message') }}</p>
    @endif

    <form class="import-bar" wire:submit="import">
        <div class="import-copy"><strong>Impor data stok</strong><span>Unggah file Excel .xlsx atau .xls</span></div>
        <input id="stock-excel-file" type="file" accept=".xlsx,.xls" wire:model="excelFile">
        <button class="btn-soft" type="submit" wire:loading.attr="disabled" wire:target="import">Pilih file & impor</button>
        <span wire:loading wire:target="import">Memproses file...</span>
        @error('excelFile') <span class="error">{{ $message }}</span> @enderror
    </form>
    <section class="panel ai-panel">
        <div class="section-heading">
            <div><p class="eyebrow">Asisten inventaris</p><h2>Buat draf dengan AI</h2></div>
            <span class="ai-pill">AI</span>
        </div>
        <p class="muted">AI menyiapkan draf untuk ditinjau. Data baru tersimpan setelah Anda memilih item.</p>
        <form wire:submit="generateWithAi" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:240px">
                <label for="ai-stock-prompt">Konteks atau jenis barang</label>
                <input id="ai-stock-prompt" type="text" wire:model="aiPrompt" placeholder="Contoh: perlengkapan kantor">
                @error('aiPrompt') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ai-stock-count">Jumlah draf</label>
                <input id="ai-stock-count" type="number" min="1" max="10" wire:model="aiCount" style="width:90px">
                @error('aiCount') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="generateWithAi">Buat draf</button>
            <span wire:loading wire:target="generateWithAi">AI sedang membuat draf...</span>
        </form>
        @if ($generatedItems)
            <form wire:submit="saveGeneratedItems" style="margin-top:18px">
                <div style="overflow-x:auto">
                    <table>
                        <thead><tr><th>Pilih</th><th>Nama</th><th>SKU</th><th>Jumlah</th><th>Satuan</th><th>Harga</th><th>Min. stok</th></tr></thead>
                        <tbody>
                            @foreach ($generatedItems as $index => $item)
                                <tr wire:key="ai-stock-{{ $index }}">
                                    <td><input type="checkbox" value="{{ $index }}" wire:model="selectedAiItems" aria-label="Pilih {{ $item['name'] }}"></td>
                                    <td>{{ $item['name'] }}</td><td>{{ $item['sku'] ?? '—' }}</td>
                                    <td>{{ $item['quantity'] }}</td><td>{{ $item['unit'] }}</td>
                                    <td>{{ $item['price'] === null ? '—' : 'Rp '.number_format($item['price'], 0, ',', '.') }}</td>
                                    <td>{{ $item['min_stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('selectedAiItems') <p class="error">{{ $message }}</p> @enderror
                <button type="submit" style="margin-top:12px">Simpan item terpilih</button>
            </form>
        @endif
    </section>

    <section class="cards">
        <article class="card"><p>Jenis barang</p><strong>{{ number_format($totalProducts) }}</strong><span class="metric-caption">item tercatat</span></article>
        <article class="card"><p>Total unit stok</p><strong>{{ number_format($totalQuantity) }}</strong><span class="metric-caption">unit tersedia</span></article>
        <article class="card"><p>Stok menipis</p><strong>{{ number_format($lowStockCount) }}</strong><span class="metric-caption">perlu diperiksa</span></article>
        <article class="card"><p>Stok habis</p><strong>{{ number_format($outOfStockCount) }}</strong><span class="metric-caption">perlu diisi ulang</span></article>
    </section>

    <section class="panel stock-table-panel">
        <div class="section-heading">
            <div><p class="eyebrow">Kelola barang</p><h2>Semua stok</h2></div>
            <span class="table-count">{{ $totalProducts }} barang</span>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px">
            <input type="search" placeholder="Cari nama atau SKU..."
                   wire:model.live.debounce.300ms="search" style="width:auto;min-width:240px">
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
                    <tr><th>Foto</th><th>Nama barang</th><th>SKU</th><th>Jumlah</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
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
                            <td><a href="{{ route('stocks.show', $stock) }}">{{ $stock->name }}</a></td>
                            <td>{{ $stock->sku ?? '—' }}</td>
                            <td>{{ $stock->quantity }} {{ $stock->unit }}</td>
                            <td>{{ $stock->price === null ? '—' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $stock->status }}">{{ $status?->label() ?? $stock->status }}</span></td>
                            <td>
                                <button type="button" wire:click="openEdit({{ $stock->id }})">Edit</button>
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

    @if ($showFormModal)
        <div class="modal-backdrop" wire:click.self="closeForm">
            <section class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="stock-form-title">
                <button class="modal-close" type="button" wire:click="closeForm" aria-label="Tutup">×</button>
                <h2 id="stock-form-title">{{ $editingStockId ? 'Edit barang' : 'Tambah barang' }}</h2>
                <form wire:submit="save">
                    <label for="modal-name">Nama barang</label>
                    <input id="modal-name" type="text" wire:model="name">
                    @error('name') <p class="error">{{ $message }}</p> @enderror

                    <label for="modal-sku">SKU</label>
                    <input id="modal-sku" type="text" wire:model="sku">
                    @error('sku') <p class="error">{{ $message }}</p> @enderror

                    <label>Deskripsi (Markdown)</label>
                    <div wire:ignore><div id="stock-list-description-editor"></div></div>
                    @error('description') <p class="error">{{ $message }}</p> @enderror

                    <label for="modal-quantity">Jumlah stok</label>
                    <input id="modal-quantity" type="number" min="0" wire:model="quantity">
                    @error('quantity') <p class="error">{{ $message }}</p> @enderror

                    <label for="modal-unit">Satuan</label>
                    <input id="modal-unit" type="text" wire:model="unit">
                    @error('unit') <p class="error">{{ $message }}</p> @enderror

                    <label for="modal-price">Harga (Rp)</label>
                    <input id="modal-price" type="number" min="0" step="0.01" wire:model="price">
                    @error('price') <p class="error">{{ $message }}</p> @enderror

                    <label for="modal-min-stock">Batas stok minimum</label>
                    <input id="modal-min-stock" type="number" min="0" wire:model="min_stock">
                    @error('min_stock') <p class="error">{{ $message }}</p> @enderror

                    <label for="modal-cover-image">Foto barang (maksimal 2 MB)</label>
                    <input id="modal-cover-image" type="file" accept="image/jpeg,image/png,image/webp" wire:model="coverImage">
                    @error('coverImage') <p class="error">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="coverImage">Mengunggah foto...</div>
                    @if ($coverImage)
                        <p><img src="{{ $coverImage->temporaryUrl() }}" alt="Pratinjau foto" style="max-width:180px;border-radius:8px"></p>
                    @elseif ($currentImagePath)
                        <p><img src="{{ Storage::disk('public')->url($currentImagePath) }}" alt="Foto barang saat ini" style="max-width:180px;border-radius:8px"></p>
                    @endif

                    <div style="display:flex;gap:10px;justify-content:flex-end">
                        <button type="button" wire:click="closeForm">Batal</button>
                        <button type="submit" wire:loading.attr="disabled">Simpan</button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>

@script
<script>
    let descriptionEditor = null;

    const initializeDescriptionEditor = () => {
        const element = $wire.$el.querySelector('#stock-list-description-editor');
        if (!element || descriptionEditor) return;

        descriptionEditor = new window.StockMarkdownEditor({
            el: element,
            height: '220px',
            initialEditType: 'markdown',
            previewStyle: 'vertical',
            initialValue: $wire.description,
            usageStatistics: false,
        });

        descriptionEditor.on('change', () => $wire.$set('description', descriptionEditor.getMarkdown()));
    };

    const destroyDescriptionEditor = () => {
        if (descriptionEditor) {
            descriptionEditor.destroy();
            descriptionEditor = null;
        }
    };

    $wire.$watch('showFormModal', isOpen => {
        if (isOpen) {
            setTimeout(initializeDescriptionEditor, 0);
        } else {
            destroyDescriptionEditor();
        }
    });

    $wire.$watch('description', value => {
        if (descriptionEditor && value !== descriptionEditor.getMarkdown()) {
            descriptionEditor.setMarkdown(value);
        }
    });
</script>
@endscript
