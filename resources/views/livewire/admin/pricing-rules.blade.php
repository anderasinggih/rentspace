<div>
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Promo & Pricing Rules</h1>
                <p class="mt-2 text-sm text-muted-foreground">Manage dynamic discounts, like "35% off for 12 hours" or "Rent 1 day, free 1 day".</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                @if(auth()->user()->role === 'admin')
                <button wire:click="create" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                    Create Rule
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
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-foreground sm:pl-6">Nama Promo</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Tipe</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Value</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Syarat Min/Durasi</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Status</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($rules as $rule)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-foreground sm:pl-6">{{ $rule->nama_promo }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">
                                        @if($rule->tipe === 'diskon_persen') Diskon Persen
                                        @elseif($rule->tipe === 'hari_gratis') Hari Gratis
                                        @else Fix Price @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-primary">
                                        @if($rule->tipe === 'diskon_persen') {{ $rule->value }}%
                                        @elseif($rule->tipe === 'hari_gratis') {{ $rule->value }} Hari
                                        @else Rp {{ number_format($rule->value, 0, ',', '.') }} @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">
                                        {{ $rule->syarat_minimal_durasi ? '> '.$rule->syarat_minimal_durasi.' '.$rule->syarat_tipe_durasi : 'Tanpa Syarat' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">
                                        @if($rule->is_active)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 border-green-200">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold bg-muted text-muted-foreground border-border">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        @if(auth()->user()->role === 'admin')
                                        <button wire:click="edit({{ $rule->id }})" class="text-primary hover:underline group-hover:text-primary/80 mr-3">Edit</button>
                                        <button wire:click="delete({{ $rule->id }})" wire:confirm="Hapus aturan ini?" class="text-red-500 hover:text-red-700">Del</button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-sm text-muted-foreground">Belum ada promo / rules yang dibuat.</td>
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
                    <h2 class="text-lg font-semibold">{{ $isEditing ? 'Edit Rule' : 'Tambah Rule / Promo Baru' }}</h2>
                    <form wire:submit="save" class="mt-6 space-y-4">
                        <div>
                            <label class="text-sm font-medium leading-none">Nama Promo / Rule</label>
                            <input type="text" wire:model="nama_promo" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Diskon Lebaran 35% / Setengah Hari">
                            @error('nama_promo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium leading-none">Tipe Potongan</label>
                                <select wire:model="tipe" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    <option value="diskon_persen">Diskon Persentase (%)</option>
                                    <option value="hari_gratis">Gratis Hari Tambahan</option>
                                    <option value="fix_price">Harga Pas (Fix Price)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium leading-none">Value Potongan</label>
                                <input type="number" wire:model="value" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Misal: 35">
                                @error('value') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-border pt-4 mt-2">
                            <div>
                                <label class="text-sm font-medium leading-none">Berlaku Minimal Durasi (Opsional)</label>
                                <input type="number" wire:model="syarat_minimal_durasi" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Misal: 12">
                                <p class="text-[10px] text-muted-foreground mt-1">Kosongkan jika selalu berlaku.</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium leading-none">Satuan Durasi Syarat</label>
                                <select wire:model="syarat_tipe_durasi" class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    <option value="jam">Jam</option>
                                    <option value="hari">Hari</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 mt-2">
                            <input type="checkbox" id="is_active_rule" wire:model="is_active" class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                            <label for="is_active_rule" class="text-sm font-medium leading-none cursor-pointer">Rule Aktif</label>
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
