<div>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Manajemen Kategori</h1>
            <p class="mt-2 text-sm text-muted-foreground">Kelola kategori unit untuk pengelompokan di katalog home dan filter admin.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <button wire:click="create" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                Tambah Kategori
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="mt-8 flex flex-col sm:flex-row gap-4 items-end sm:items-center justify-between">
        <div class="relative flex-1 max-w-sm">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" class="block w-full h-9 pl-10 pr-3 text-sm rounded-md border border-input bg-background shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Cari nama kategori...">
        </div>
    </div>

    @if (session()->has('error'))
        <div class="mt-4 p-4 bg-destructive/10 border border-destructive/20 text-destructive rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-4 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr class="bg-muted/50 text-left">
                                <th scope="col" class="py-3 px-3 sm:pl-6 text-sm font-semibold text-foreground">Nama Kategori</th>
                                <th scope="col" class="px-3 py-3 text-sm font-semibold text-foreground">Slug (URL)</th>
                                <th scope="col" class="px-3 py-3 text-sm font-semibold text-foreground text-right pr-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($categories as $cat)
                                <tr class="hover:bg-muted/50 transition-colors">
                                    <td class="px-3 py-4 sm:pl-6 align-middle">
                                        <div class="font-bold text-sm text-foreground flex items-center gap-2">
                                            @if($cat->icon)
                                                <span class="text-lg">{{ $cat->icon }}</span>
                                            @endif
                                            {{ $cat->name }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 align-middle">
                                        <code class="text-xs bg-muted px-1.5 py-0.5 rounded text-muted-foreground">{{ $cat->slug }}</code>
                                    </td>
                                    <td class="px-3 py-4 align-middle text-right pr-2 sm:pr-6 whitespace-nowrap">
                                        <button wire:click="edit({{ $cat->id }})" class="text-primary hover:underline text-xs sm:text-sm font-semibold mr-3">Edit</button>
                                        <button wire:click="delete({{ $cat->id }})" class="text-destructive hover:underline text-xs sm:text-sm font-semibold" onclick="confirm('Yakin ingin menghapus kategori ini? Pastikan tidak ada unit yang menggunakan kategori ini.') || event.stopImmediatePropagation()">Hapus</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($categories->isEmpty())
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-muted-foreground">Belum ada data kategori.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="relative z-50">
        <div class="fixed inset-0 bg-background/80 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-xl border border-border bg-background p-6 shadow-lg sm:p-8 overflow-y-auto max-h-[90vh]">
                <h2 class="text-lg font-semibold">{{ $isEditing ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h2>
                <form wire:submit="save" class="mt-6 space-y-4">
                    <div>
                        <label class="text-sm font-medium leading-none">Nama Kategori</label>
                        <input type="text" wire:model.live="name" class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="iPhone, Gear, Android, dll">
                        @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium leading-none">Slug (Otomatis)</label>
                        <input type="text" wire:model="slug" class="mt-2 flex h-10 w-full rounded-md border border-input bg-muted/50 px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" readonly>
                        @error('slug') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium leading-none">Icon / Emoji (Opsional)</label>
                        <input type="text" wire:model="icon" class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder=", 🎥, 🎮, dll">
                        @error('icon') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-border">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold">Input Spesifikasi Kustom</label>
                            <button type="button" wire:click="addField" class="text-xs bg-secondary text-secondary-foreground px-2 py-1 rounded-md hover:bg-secondary/80 transition-colors">
                                + Tambah Field
                            </button>
                        </div>
                        <p class="text-[11px] text-muted-foreground mb-4">Tambahkan kolom input khusus untuk kategori ini (misal: Lensa, Zoom, resolusi, dll).</p>
                        
                        <div class="space-y-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($fields as $index => $field)
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <input type="text" wire:model="fields.{{ $index }}" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Nama Field (misal: Lensa)">
                                </div>
                                <button type="button" wire:click="removeField({{ $index }})" class="p-2 text-destructive hover:bg-destructive/10 rounded-md transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                            @endforeach
                            
                            @if(count($fields) === 0)
                            <div class="text-center py-4 border-2 border-dashed border-border rounded-lg">
                                <p class="text-xs text-muted-foreground uppercase tracking-wider">Tidak ada field kustom</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showModal', false)" class="inline-flex items-center justify-center rounded-md border border-input bg-background h-9 px-4 text-sm font-medium shadow-sm hover:bg-muted hover:text-foreground">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary h-9 px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
