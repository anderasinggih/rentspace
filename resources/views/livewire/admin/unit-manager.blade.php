<div>
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Unit Management</h1>
                <p class="mt-2 text-sm text-muted-foreground">List all rental items across categories (iPhone and Gear).</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                @if(auth()->user()->role === 'admin')
                <button wire:click="create" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    Add new unit
                </button>
                @endif
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th scope="col" class="py-3 pl-3 pr-3 text-left text-xs sm:text-sm font-semibold text-foreground sm:pl-6">Nama & Info</th>
                                    <th scope="col" class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">Detail Spesifikasi</th>
                                    <th scope="col" class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">Harga Sewa</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs sm:text-sm font-semibold text-foreground">Status</th>
                                    <th scope="col" class="relative py-3 pl-3 pr-2 sm:pr-6"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                        @foreach($units as $unit)
                            <tr class="hover:bg-muted/50 transition-colors {{ $unit->trashed() ? 'bg-red-500/5 opacity-60 grayscale' : (!$unit->is_active ? 'opacity-50' : '') }}">
                                <td class="px-3 sm:px-6 py-3 sm:py-4 align-middle">
                                    <div class="font-bold text-xs sm:text-sm {{ $unit->trashed() ? 'line-through text-muted-foreground' : '' }}">
                                        {{ $unit->seri }}
                                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md border @if($unit->kategori === 'gear') bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border-purple-200/50 dark:border-purple-900/50 @else bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border-blue-200/50 dark:border-blue-900/50 @endif font-medium uppercase">
                                            {{ $unit->kategori === 'gear' ? 'Alat' : 'Ponsel' }}
                                        </span>
                                    </div>
                                    @if($unit->imei)
                                        <div class="text-xs text-muted-foreground">{{ $unit->imei }}</div>
                                    @endif
                                    {{-- Specs + price shown only on mobile --}}
                                    <div class="sm:hidden mt-1 space-y-0.5">
                                        @if($unit->warna || $unit->memori)
                                            <div class="text-xs text-muted-foreground">{{ $unit->warna }} · {{ $unit->memori }}</div>
                                        @endif
                                        <div class="text-xs font-semibold text-foreground">Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }}/hari · Rp {{ number_format($unit->harga_per_jam, 0, ',', '.') }}/jam</div>
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell px-6 py-4 align-middle">
                                    @if($unit->kategori === 'iphone')
                                        <div class="text-sm">{{ $unit->warna }} - {{ $unit->memori }}</div>
                                    @else
                                        <div class="text-sm text-muted-foreground italic">Non-spesifikasi ponsel</div>
                                    @endif
                                    @if($unit->kondisi)
                                        <div class="text-[11px] text-muted-foreground mt-0.5">{{ $unit->kondisi }}</div>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell px-6 py-4 align-middle">
                                    <div class="text-sm font-semibold">Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }} / hari</div>
                                    <div class="text-xs text-muted-foreground">Rp {{ number_format($unit->harga_per_jam, 0, ',', '.') }} / jam</div>
                                </td>
                                <td class="px-2 sm:px-6 py-3 sm:py-4 align-middle">
                                    @if($unit->trashed())
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] sm:text-xs font-semibold bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300 border-red-200/50 dark:border-red-900/50">Dihapus</span>
                                    @elseif($unit->is_active)
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] sm:text-xs font-semibold bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300 border-green-200/50 dark:border-green-900/50">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] sm:text-xs font-semibold bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200/50 dark:border-zinc-700/50">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-2 sm:px-6 py-3 sm:py-4 align-middle text-right h-full">
                                    <div class="flex items-center justify-end w-full gap-2 sm:gap-4">
                                        @if($unit->trashed())
                                            @if(auth()->user()->role === 'admin')
                                            <button wire:click="restoreUnit({{ $unit->id }})" class="inline-flex items-center justify-center rounded-md text-xs sm:text-sm font-medium transition-colors bg-emerald-100 text-emerald-700 hover:bg-emerald-200 h-7 sm:h-8 px-2 sm:px-4">Pulihkan</button>
                                            @endif
                                        @else
                                            @if(auth()->user()->role === 'admin')
                                            <button wire:click="edit({{ $unit->id }})" class="text-primary hover:underline text-xs sm:text-sm font-semibold">Edit</button>
                                            <button wire:click="delete({{ $unit->id }})" class="text-destructive hover:underline text-xs sm:text-sm font-semibold" onclick="confirm('Yakin ingin menghapus unit ini?') || event.stopImmediatePropagation()">Hapus</button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if(count($units) == 0)
                            <tr>
                                <td colspan="5" class="p-8 text-center text-muted-foreground">Belum ada data unit iPhone.</td>
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
                <div class="relative w-full max-w-lg rounded-xl border border-border bg-background p-6 shadow-lg sm:p-8">
                    <h2 class="text-lg font-semibold">{{ $isEditing ? 'Edit Item Sewa' : 'Tambah Item Sewa Baru' }}</h2>
                    <form wire:submit="save" class="mt-6 space-y-4">
                        <div>
                            <label class="text-sm font-medium leading-none">Kategori Item</label>
                            <select wire:model.live="kategori" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                <option value="iphone">iPhone (Ponsel)</option>
                                <option value="gear">Alat / Aksesoris (Lainnya)</option>
                            </select>
                            @error('kategori') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 @if($kategori === 'iphone') sm:grid-cols-2 @endif gap-4">
                            <div>
                                <label class="text-sm font-medium leading-none">{{ $kategori === 'iphone' ? 'Seri iPhone' : 'Nama Barang' }}</label>
                                <input type="text" wire:model="seri" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="{{ $kategori === 'iphone' ? 'iPhone 15 Pro Max' : 'Tripod Takara / Powerbank' }}">
                                @error('seri') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            @if($kategori === 'iphone')
                            <div>
                                <label class="text-sm font-medium leading-none">IMEI</label>
                                <input type="text" wire:model="imei" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('imei') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            @endif
                        </div>

                        @if($kategori === 'iphone')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium leading-none">Memori / Kapasitas</label>
                                <input type="text" wire:model="memori" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="256 GB">
                                @error('memori') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium leading-none">Warna</label>
                                <input type="text" wire:model="warna" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Natural Titanium">
                                @error('warna') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="text-sm font-medium leading-none">Kondisi</label>
                            <input type="text" wire:model="kondisi" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Mulus, BH 98%">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium leading-none">Harga Sewa / Jam</label>
                                <input type="number" wire:model="harga_per_jam" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('harga_per_jam') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium leading-none">Harga Sewa / Hari</label>
                                <input type="number" wire:model="harga_per_hari" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('harga_per_hari') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 mt-2">
                            <input type="checkbox" id="is_active" wire:model="is_active" class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                            <label for="is_active" class="text-sm font-medium leading-none cursor-pointer">Unit Aktif (Tersedia disewa)</label>
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
</div>
