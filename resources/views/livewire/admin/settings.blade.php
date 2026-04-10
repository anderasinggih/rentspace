<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Pengaturan Web</h1>
        <p class="mt-2 text-sm text-muted-foreground">Kelola konfigurasi website seperti QRIS Pembayaran.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- QRIS Setting -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">QRIS Pembayaran</h2>
                <p class="text-xs text-muted-foreground">Unggah barcode / gambar QRIS yang akan ditampilkan di halaman pembayaran.</p>
            </div>
            <div class="p-4">
                @if (session()->has('message'))
                    <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200" role="alert">
                        {{ session('message') }}
                    </div>
                @endif
                
                <div class="mb-4 flex items-center justify-center">
                    <div class="w-48 h-48 bg-muted border border-dashed border-border rounded-lg flex items-center justify-center relative overflow-hidden">
                        @if ($qris_photo)
                            <img src="{{ $qris_photo->temporaryUrl() }}" class="w-full h-full object-cover">
                        @else
                            <img src="{{ Storage::url('public/qris.jpg') }}?t={{ time() }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" class="absolute inset-0 w-full h-full object-cover">
                            <span style="display:none;" class="text-muted-foreground text-xs font-medium z-10 w-full text-center">Logo Kosong</span>
                        @endif
                    </div>
                </div>

                <form wire:submit="saveQris" class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Unggah Foto (Rekomendasi 1:1, Max 2MB)</label>
                        <input type="file" wire:model="qris_photo" accept="image/*" class="w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                        @error('qris_photo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveQris">Simpan QRIS Baru</span>
                        <span wire:loading wire:target="saveQris">Menyimpan...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
