<div class="panel">
    <p><a href="{{ route('stocks.show', $stockId) }}">← Kembali ke detail stok</a></p>
    <h1>Edit barang</h1>

    <form wire:submit="save">
        <label for="name">Nama barang</label>
        <input id="name" type="text" wire:model="name">
        @error('name') <p class="error">{{ $message }}</p> @enderror

        <label for="sku">SKU</label>
        <input id="sku" type="text" wire:model="sku">
        @error('sku') <p class="error">{{ $message }}</p> @enderror

        <label>Deskripsi (Markdown)</label>
        <div wire:ignore><div id="stock-description-editor"></div></div>
        @error('description') <p class="error">{{ $message }}</p> @enderror

        <label for="quantity">Jumlah stok</label>
        <input id="quantity" type="number" min="0" wire:model="quantity">
        @error('quantity') <p class="error">{{ $message }}</p> @enderror

        <label for="unit">Satuan</label>
        <input id="unit" type="text" wire:model="unit">
        @error('unit') <p class="error">{{ $message }}</p> @enderror

        <label for="price">Harga (Rp)</label>
        <input id="price" type="number" min="0" step="0.01" wire:model="price">
        @error('price') <p class="error">{{ $message }}</p> @enderror

        <label for="min_stock">Batas stok minimum</label>
        <input id="min_stock" type="number" min="0" wire:model="min_stock">
        @error('min_stock') <p class="error">{{ $message }}</p> @enderror

        <label for="coverImage">Ganti foto barang (maksimal 2 MB)</label>
        <input id="coverImage" type="file" accept="image/*" wire:model="coverImage">
        @error('coverImage') <p class="error">{{ $message }}</p> @enderror
        <div wire:loading wire:target="coverImage">Mengunggah foto...</div>
        @if ($coverImage)
            <p><img src="{{ $coverImage->temporaryUrl() }}" alt="Pratinjau foto baru" style="max-width:180px;border-radius:8px"></p>
        @elseif ($currentImagePath)
            <p><img src="{{ Storage::disk('public')->url($currentImagePath) }}" alt="Foto barang" style="max-width:180px;border-radius:8px"></p>
        @endif

        <button type="submit" wire:loading.attr="disabled">Simpan perubahan</button>
    </form>
</div>

@script
<script>
    const descriptionEditor = new window.StockMarkdownEditor({
        el: document.querySelector('#stock-description-editor'),
        height: '280px',
        initialEditType: 'markdown',
        previewStyle: 'vertical',
        initialValue: @js($description),
        usageStatistics: false,
    });

    descriptionEditor.on('change', () => $wire.$set('description', descriptionEditor.getMarkdown()));
    $wire.$watch('description', value => {
        if (value !== descriptionEditor.getMarkdown()) descriptionEditor.setMarkdown(value);
    });
</script>
@endscript
