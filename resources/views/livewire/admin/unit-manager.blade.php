<div x-data="{ activeTab: @entangle('activeTab') }">
    <div class="space-y-6">
        <!-- Header & Tabs -->
        <div class="sm:flex sm:items-center sm:justify-between border-b pb-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Inventory Management</h1>
                <p class="text-sm text-muted-foreground">Manage your hardware units and product categories in one place.</p>
                
                <div class="flex items-center gap-1 bg-muted p-1 rounded-lg mt-4 w-fit">
                    <button @click="activeTab = 'units'; $wire.setTab('units')" 
                        :class="activeTab === 'units' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-background/50'"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all">
                        Daftar Unit
                    </button>
                    <button @click="activeTab = 'categories'; $wire.setTab('categories')" 
                        :class="activeTab === 'categories' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-background/50'"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all">
                        Manajemen Kategori
                    </button>
                </div>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0">
                @if(auth()->user()->role === 'admin')
                    <template x-if="activeTab === 'units'">
                        <button wire:click="create" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Add New Unit
                        </button>
                    </template>
                    <template x-if="activeTab === 'categories'">
                        <button wire:click="createCat" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Create Category
                        </button>
                    </template>
                @endif
            </div>
        </div>

        @if(session()->has('message'))
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 px-4 py-2 rounded-md text-sm font-medium">
                {{ session('message') }}
            </div>
        @endif

        @if(session()->has('error'))
            <div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-2 rounded-md text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <!-- Global Search -->
        <div class="flex flex-col sm:flex-row gap-4 items-end sm:items-center justify-between">
            <div class="flex flex-1 flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <div class="relative flex-1 max-w-sm">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        class="block w-full h-9 pl-10 pr-3 text-sm rounded-md border border-input bg-background shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" 
                        :placeholder="activeTab === 'units' ? 'Cari seri, IMEI, warna...' : 'Cari kategori...'">
                </div>
                
                <template x-if="activeTab === 'units'">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <select wire:model.live="filterKategori" class="h-9 w-full sm:w-[150px] rounded-md border border-input bg-background px-3 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="">Semua Kategori</option>
                            @foreach($all_categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="filterStatus" class="h-9 w-full sm:w-[150px] rounded-md border border-input bg-background px-3 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="">Semua Status</option>
                            <option value="active">🟢 Aktif</option>
                            <option value="inactive">⚪ Nonaktif</option>
                            <option value="deleted">🔴 Dihapus</option>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        <!-- Content Area -->
        <div class="mt-4">
            <!-- Units Tab -->
            <div x-show="activeTab === 'units'" class="flow-root animate-in fade-in duration-300">
                <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr class="bg-muted/50 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                <th scope="col" class="py-3 px-6 text-left">Nama & Info</th>
                                <th scope="col" class="hidden sm:table-cell px-6 py-3 text-left">Detail Spesifikasi</th>
                                <th scope="col" class="hidden sm:table-cell px-6 py-3 text-left">Harga Sewa</th>
                                <th scope="col" class="px-6 py-3 text-left">Status</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                    @foreach($units as $unit)
                        <tr class="hover:bg-muted/50 transition-colors {{ $unit->trashed() ? 'bg-red-500/5 opacity-60 grayscale' : (!$unit->is_active ? 'opacity-50' : '') }}">
                            <td class="px-6 py-4 align-middle">
                                <div class="font-bold text-sm {{ $unit->trashed() ? 'line-through text-muted-foreground' : '' }}">
                                    {{ $unit->seri }}
                                    @if($unit->category)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-accent px-2 py-0.5 text-[10px] font-medium text-accent-foreground uppercase leading-none">
                                        {{ $unit->category->name }}
                                    </span>
                                    @endif
                                </div>
                                @if($unit->imei)
                                    <div class="text-xs text-muted-foreground font-mono mt-0.5 opacity-70">{{ $unit->imei }}</div>
                                @endif
                                <div class="sm:hidden mt-2 space-y-0.5 text-[11px] text-muted-foreground">
                                    @if($unit->warna || $unit->memori)
                                        <div>{{ $unit->warna }} · {{ $unit->memori }}</div>
                                    @endif
                                    <div class="font-semibold text-foreground">Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }}/hari</div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 align-middle">
                                @if($unit->category && str_contains(strtolower($unit->category->slug), 'iphone'))
                                    <div class="text-sm font-medium">{{ $unit->warna }} - {{ $unit->memori }}</div>
                                @else
                                    @if($unit->specs && count($unit->specs) > 0)
                                        <div class="flex flex-wrap gap-x-3 gap-y-1">
                                            @foreach($unit->specs as $key => $val)
                                                @if($val)
                                                <div class="text-[11px] whitespace-nowrap"><span class="font-semibold text-muted-foreground uppercase text-[9px] mr-1">{{ $key }}:</span> {{ $val }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-sm text-muted-foreground italic opacity-50">Umum</div>
                                    @endif
                                @endif
                                @if($unit->kondisi)
                                    <div class="text-[10px] text-muted-foreground mt-1 line-clamp-1 italic px-2 py-0.5 bg-muted/60 rounded border border-border/50 w-fit">{{ $unit->kondisi }}</div>
                                @endif
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 align-middle">
                                <div class="text-sm font-bold">Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }} / hari</div>
                                <div class="text-[10px] text-muted-foreground">Rp {{ number_format($unit->harga_per_jam, 0, ',', '.') }} / jam</div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                @if($unit->trashed())
                                    <span class="inline-flex items-center rounded-full bg-red-500/10 px-2 py-0.5 text-xs font-semibold text-red-600">Dihapus</span>
                                @elseif($unit->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-500/10 px-2 py-0.5 text-xs font-semibold text-zinc-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if($unit->trashed())
                                        @if(auth()->user()->role === 'admin')
                                        <button wire:click="restoreUnit({{ $unit->id }})" class="text-emerald-600 hover:text-emerald-700 text-xs font-bold transition-colors">Pulihkan</button>
                                        @endif
                                    @else
                                        @if(auth()->user()->role === 'admin')
                                        <button wire:click="edit({{ $unit->id }})" class="text-primary hover:text-primary/80 text-xs font-bold transition-colors underline decoration-primary/30 underline-offset-4">Edit</button>
                                        <button wire:click="delete({{ $unit->id }})" class="text-destructive hover:text-destructive/80 text-xs font-bold transition-colors underline decoration-destructive/30 underline-offset-4" onclick="confirm('Yakin ingin menghapus unit ini?') || event.stopImmediatePropagation()">Hapus</button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($units) == 0)
                        <tr>
                            <td colspan="5" class="p-16 text-center text-muted-foreground italic">No units found matching your search.</td>
                        </tr>
                    @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Categories Tab -->
            <div x-show="activeTab === 'categories'" class="flow-root animate-in fade-in duration-300">
                <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr class="bg-muted/50 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                <th scope="col" class="py-3 px-6 text-left w-1/4">Category Name</th>
                                <th scope="col" class="px-6 py-3 text-left w-1/4">Slug / Identifier</th>
                                <th scope="col" class="px-6 py-3 text-left">Custom Specs Fields</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                    @foreach($categories as $cat)
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded bg-primary/10 flex items-center justify-center text-primary font-bold">
                                        {{ substr($cat->name, 0, 1) }}
                                    </div>
                                    <span class="font-bold text-sm">{{ $cat->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <code class="text-xs font-mono bg-muted px-2 py-0.5 rounded text-muted-foreground">{{ $cat->slug }}</code>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                @if($cat->custom_fields && count($cat->custom_fields) > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($cat->custom_fields as $f)
                                            <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 border border-zinc-200">
                                                {{ $f }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-muted-foreground italic opacity-50">Standard fields only</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if(auth()->user()->role === 'admin')
                                    <button wire:click="editCat({{ $cat->id }})" class="text-primary hover:text-primary/80 text-xs font-bold transition-colors underline decoration-primary/30 underline-offset-4">Edit</button>
                                    <button wire:click="deleteCat({{ $cat->id }})" class="text-destructive hover:text-destructive/80 text-xs font-bold transition-colors underline decoration-destructive/30 underline-offset-4" onclick="confirm('Yakin ingin menghapus kategori ini? Semua field spek khusus akan ikut terhapus.') || event.stopImmediatePropagation()">Hapus</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($categories) == 0)
                        <tr>
                            <td colspan="4" class="p-16 text-center text-muted-foreground italic">No categories found matching your search.</td>
                        </tr>
                    @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Unit Modal -->
        @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="relative w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-2xl sm:p-8 animate-in zoom-in-95 duration-200 overflow-y-auto max-h-[90vh]">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold tracking-tight">{{ $isEditing ? 'Edit Hardware Unit' : 'Configure New Unit' }}</h2>
                    <button wire:click="$set('showModal', false)" class="text-muted-foreground hover:text-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                
                <form wire:submit="save" class="space-y-5">
                    @php
                        $selectedCat = $all_categories->find($category_id);
                        $isIphone = $selectedCat && str_contains(strtolower($selectedCat->slug), 'iphone');
                    @endphp
                    <div>
                        <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Kategori Item</label>
                        <select wire:model.live="category_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($all_categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 @if($isIphone) sm:grid-cols-2 @endif gap-4">
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">{{ $isIphone ? 'Seri iPhone' : 'Nama Barang' }}</label>
                            <input type="text" wire:model="seri" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="{{ $isIphone ? 'iPhone 15 Pro Max' : 'Tripod Takara' }}">
                            @error('seri') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                        @if($isIphone)
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">IMEI</label>
                            <input type="text" wire:model="imei" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors font-mono focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('imei') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                        @endif
                    </div>

                    @if($isIphone)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Memori</label>
                            <input type="text" wire:model="memori" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="256 GB">
                            @error('memori') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Warna</label>
                            <input type="text" wire:model="warna" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Natural Titanium">
                            @error('warna') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Kondisi (Opsional)</label>
                        <input type="text" wire:model="kondisi" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Mulus, BH 98%">
                    </div>

                    @if($selectedCat && $selectedCat->custom_fields && count($selectedCat->custom_fields) > 0)
                    <div class="pt-4 border-t border-border">
                        <label class="text-[10px] font-black uppercase tracking-wider text-primary mb-3 block">Spesifikasi Khusus: {{ $selectedCat->name }}</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($selectedCat->custom_fields as $fieldName)
                            <div>
                                <label class="text-[11px] font-bold text-foreground mb-1 block">{{ $fieldName }}</label>
                                <input type="text" wire:model="specs.{{ $fieldName }}" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="...">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Harga / Jam</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">Rp</span>
                                <input type="number" wire:model="harga_per_jam" class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            </div>
                            @error('harga_per_jam') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Harga / Hari</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">Rp</span>
                                <input type="number" wire:model="harga_per_hari" class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            </div>
                            @error('harga_per_hari') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 bg-muted/40 p-3 rounded-lg border border-border">
                        <input type="checkbox" id="is_active" wire:model="is_active" class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer transition-all">
                        <label for="is_active" class="text-sm font-bold text-foreground cursor-pointer select-none">Unit Aktif (Tersedia disewa)</label>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-bold text-primary-foreground shadow hover:bg-primary/90 transition-all">
                            {{ $isEditing ? 'Simpan Perubahan' : 'Daftarkan Unit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Category Modal -->
        @if($showCatModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="relative w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-2xl sm:p-8 animate-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold tracking-tight">{{ $isEditingCat ? 'Edit Category' : 'New Product Category' }}</h2>
                    <button wire:click="$set('showCatModal', false)" class="text-muted-foreground hover:text-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                
                <form wire:submit="saveCat" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-1">
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Category Name</label>
                            <input type="text" wire:model.live="cat_name" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" 
                                placeholder="iPhone, Gear, etc.">
                            @error('cat_name') <span class="text-[11px] text-destructive mt-1 font-medium block">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-1 text-xs">
                            <label class="text-xs font-black uppercase text-muted-foreground mb-1.5 block">Slug / URL Key</label>
                            <div class="flex items-center h-10 px-3 bg-muted rounded-md text-muted-foreground font-mono font-black italic">
                                {{ $cat_slug ?: 'auto-generated' }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-black uppercase text-muted-foreground block">Spesifikasi Khusus (Custom Fields)</label>
                            <button type="button" wire:click="addCatField" class="text-[10px] font-black text-primary hover:underline uppercase flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                Add Field
                            </button>
                        </div>
                        <p class="text-[11px] text-muted-foreground mb-4 leading-relaxed">Definisikan atribut spesifik untuk kategori ini (Contoh: Baterai, Ukuran Layar, Lensa, dll).</p>
                        <div class="space-y-3 max-h-40 overflow-y-auto pr-2">
                            @foreach($cat_fields as $index => $field)
                                <div class="flex items-center gap-2 animate-in slide-in-from-left duration-200">
                                    <input type="text" wire:model="cat_fields.{{ $index }}" 
                                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="Nama Field (Contoh: Baterai)">
                                    <button type="button" wire:click="removeCatField({{ $index }})" class="h-9 w-9 flex items-center justify-center text-destructive hover:bg-destructive/10 rounded-md transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </div>
                            @endforeach
                            @if(count($cat_fields) === 0)
                                <div class="text-center py-4 border-2 border-dashed rounded-lg text-muted-foreground/40 text-xs">No custom fields defined yet.</div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showCatModal', false)" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-bold text-primary-foreground shadow hover:bg-primary/90 transition-all">
                            {{ $isEditingCat ? 'Update Category' : 'Save Category' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
