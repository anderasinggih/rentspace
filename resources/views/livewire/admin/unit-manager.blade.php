<div class="min-h-screen bg-muted/30 py-8 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Unit Management</h1>
                <p class="mt-2 text-sm text-muted-foreground">A list of all iPhone units available for rent, their specs, and pricing configurations.</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button wire:click="create" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    Add new unit
                </button>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-foreground sm:pl-6">Seri & IMEI</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Spesifikasi</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Harga/Jam</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Harga/Hari</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Status</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($units as $unit)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="font-medium text-foreground">{{ $unit->seri }}</div>
                                        <div class="text-muted-foreground">{{ $unit->imei }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-foreground">
                                        {{ $unit->memori }} • {{ $unit->warna }}<br/>
                                        <span class="text-xs text-muted-foreground">{{ Str::limit($unit->kondisi, 20) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">Rp {{ number_format($unit->harga_per_jam, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">
                                        @if($unit->is_active)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 border-green-200">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold bg-muted text-muted-foreground border-border">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button wire:click="edit({{ $unit->id }})" class="text-primary hover:underline group-hover:text-primary/80 mr-3">Edit</button>
                                        <button wire:click="delete({{ $unit->id }})" wire:confirm="Yakin ingin menghapus unit ini?" class="text-red-500 hover:text-red-700">Del</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-sm text-muted-foreground">Tidak ada unit yang terdaftar.</td>
                                </tr>
                                @endforelse
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
                    <h2 class="text-lg font-semibold">{{ $isEditing ? 'Edit Unit iPhone' : 'Tambah Unit iPhone' }}</h2>
                    <form wire:submit="save" class="mt-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium leading-none">Seri iPhone</label>
                                <input type="text" wire:model="seri" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="iPhone 15 Pro Max">
                                @error('seri') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium leading-none">IMEI</label>
                                <input type="text" wire:model="imei" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('imei') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

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
