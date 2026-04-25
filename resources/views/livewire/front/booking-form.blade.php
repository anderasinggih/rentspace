

<div class="pt-0 pb-10 px-4 sm:px-6 lg:px-8 bg-background sm:min-h-[calc(100vh-4rem)]">

    <div class="max-w-3xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 sm:mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground">Formulir Penyewaan</h1>
        </div>

        <div x-data="bookingForm()" 
            class="bg-background rounded-2xl shadow-sm border border-border p-6 sm:p-8">
            <form wire:submit.prevent="submit" class="space-y-8">

                <!-- Progress Bar -->
                <div class="mb-8 border-b border-border pb-4">
                    <div class="flex items-center justify-center text-sm font-bold text-primary mb-3 px-1">
                        <span x-show="step === 1">1. Pilih Unit & Jadwal</span>
                        <span x-show="step === 2">2. Isi Data & Promo</span>
                        <span x-show="step === 3">3. Konfirmasi Pesanan</span>
                        <span x-show="step === 4">4. Proses Pembayaran</span>
                    </div>
                    <div class="h-2 bg-muted rounded-full overflow-hidden">
                        <div class="h-full bg-primary transition-all duration-500 rounded-full" 
                            x-bind:style="'width: ' + ((step / 4) * 100) + '%'"></div>
                    </div>
                </div>

                <!-- STEP 1: Jadwal & Unit -->
                <div x-show="step === 1" x-transition.opacity.duration.300ms class="space-y-8 pb-8 sm:pb-0 font-sans">
                    <!-- 1. Jadwal Sewa -->
                    <div>
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <h2 class="text-xl font-bold tracking-tight text-foreground">1. Jadwal Peminjaman</h2>
                            <a href="{{ route('public.timeline') }}" wire:navigate
                                class="inline-flex items-center justify-center rounded-lg bg-primary/10 text-primary hover:bg-primary/20 h-9 px-3.5 text-[11px] font-bold shadow-sm transition-colors border border-primary/20">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                                    <line x1="16" x2="16" y1="2" y2="6" />
                                    <line x1="8" x2="8" y1="2" y2="6" />
                                    <line x1="3" x2="21" y1="10" y2="10" />
                                </svg>
                                Jadwal
                            </a>
                        </div>
                        <div class="flex flex-row justify-between items-start w-full gap-3 sm:gap-6">
                            <!-- Waktu Mulai Button -->
                            <div class="w-[48%] sm:w-48 shrink-0">
                                <label class="text-[11px] font-bold text-muted-foreground ml-1 mb-1.5 block">Waktu Mulai</label>
                                <div class="relative h-11 cursor-pointer"
                                    x-on:click="$refs.mulaiInput.showPicker ? $refs.mulaiInput.showPicker() : $refs.mulaiInput.click()">
                                    {{-- Tombol visual --}}
                                    @if($waktu_mulai)
                                    <div class="flex items-center justify-center w-full h-11 rounded-xl border border-border bg-card/40 text-[11px] font-semibold px-2 text-center pointer-events-none select-none">
                                        <span class="text-foreground truncate">{{ \Carbon\Carbon::parse($waktu_mulai)->translatedFormat('d M Y, H:i') }}</span>
                                    </div>
                                    @else
                                    <div class="inline-flex items-center justify-center w-full h-11 rounded-xl bg-primary text-primary-foreground text-sm font-medium shadow-sm pointer-events-none select-none px-3 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5 shrink-0"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                        <span>Mulai</span>
                                    </div>
                                    @endif
                                    {{-- Input transparan: tap langsung (iOS) --}}
                                    <input type="datetime-local" wire:model.live="waktu_mulai" x-ref="mulaiInput"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        style="-webkit-appearance: none;">
                                </div>
                                @error('waktu_mulai') <span class="text-[9px] text-red-500 leading-tight block mt-1 ml-1 text-center font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Waktu Selesai Button -->
                            <div class="w-[48%] sm:w-48 shrink-0">
                                <label class="text-[11px] font-bold text-muted-foreground ml-1 mb-1.5 block">Waktu Selesai</label>
                                <div class="relative h-11 cursor-pointer"
                                    x-on:click="$refs.selesaiInput.showPicker ? $refs.selesaiInput.showPicker() : $refs.selesaiInput.click()">
                                    {{-- Tombol visual --}}
                                    @if($waktu_selesai)
                                    <div class="flex items-center justify-center w-full h-11 rounded-xl border border-border bg-card/40 text-[11px] font-semibold px-2 text-center pointer-events-none select-none">
                                        <span class="text-foreground truncate">{{ \Carbon\Carbon::parse($waktu_selesai)->translatedFormat('d M Y, H:i') }}</span>
                                    </div>
                                    @else
                                    <div class="inline-flex items-center justify-center w-full h-11 rounded-xl bg-primary text-primary-foreground text-sm font-medium shadow-sm pointer-events-none select-none px-3 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5 shrink-0"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                        <span>Selesai</span>
                                    </div>
                                    @endif
                                    {{-- Input transparan: tap langsung (iOS) --}}
                                    <input type="datetime-local" wire:model.live="waktu_selesai" x-ref="selesaiInput"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        style="-webkit-appearance: none;">
                                </div>
                                @error('waktu_selesai') <span class="text-[9px] text-red-500 leading-tight block mt-1 ml-1 text-center font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                <!-- 2. Pilihan Unit -->
                <div class="space-y-6">
                    <div class="flex flex-col gap-4">
                        <h2 class="text-xl font-bold tracking-tight mb-2 text-foreground">2. Pilih Unit Tersedia</h2>
                        
                        @if($waktu_mulai && $waktu_selesai)
                        <!-- Filter & Search Bar -->
                        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                            {{-- Categories Dropdown --}}
                            <div class="w-full sm:w-auto relative">
                                <select wire:model.live="selected_category_id" 
                                    class="appearance-none block w-full sm:w-48 pl-3 pr-10 py-2 text-xs border border-border rounded-lg bg-background focus:ring-1 focus:ring-primary outline-none transition-all font-bold shadow-sm">
                                    <option value="">Semua Kategori</option>
                                    @foreach($categories_list as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </div>

                            {{-- Search field --}}
                            <div class="relative w-full sm:w-64">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                </span>
                                <input type="text" wire:model.live.debounce.500ms="unit_search" 
                                    placeholder="Cari seri, warna..." 
                                    class="block w-full pl-9 pr-3 py-2 text-xs border border-border rounded-lg bg-background focus:ring-1 focus:ring-primary outline-none transition-all shadow-sm">
                            </div>
                        </div>



                        {{-- RESULT LIST --}}
                        <div class="space-y-4 relative">
                            <!-- Global Loader for availability check -->
                            <div wire:loading wire:target="checkAvailability, selected_category_id, unit_search, waktu_mulai, waktu_selesai" 
                                class="absolute inset-0 bg-background/50 backdrop-blur-[1px] z-30 flex items-center justify-center rounded-xl">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                    <span class="text-[10px] font-bold text-primary uppercase tracking-widest">Memperbarui Unit...</span>
                                </div>
                            </div>

                            @if(count($available_units) > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($available_units as $unit)
                                <label
                                    x-bind:class="selectedIds.includes({{ $unit->id }}) || selectedIds.includes('{{ $unit->id }}') ? 'border-primary ring-1 ring-primary bg-primary/[0.04]' : 'border-border bg-background hover:border-primary/50'"
                                    class="group relative flex cursor-pointer rounded-xl border p-3.5 shadow-sm transition-all focus:outline-none items-center justify-between">
                                    <input type="checkbox" wire:model.live="selected_unit_ids" value="{{ $unit->id }}"
                                        class="sr-only">
                                    
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-sm text-foreground truncate leading-tight group-hover:text-primary transition-colors">
                                            {{ $unit->seri }}
                                        </span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[10px] font-medium text-muted-foreground text-left">
                                                {{ $unit->warna }}@if($unit->warna && $unit->memori) • @endif{{ $unit->memori }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <div class="flex flex-col items-end text-right shrink-0">
                                            <div class="text-xs font-black text-primary">
                                                Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }}
                                            </div>
                                            <span class="text-[9px] font-medium text-muted-foreground leading-none mt-0.5">/ hari</span>
                                        </div>

                                        <!-- Indicator -->
                                        <div class="flex items-center">
                                            <template x-if="selectedIds.includes({{ $unit->id }}) || selectedIds.includes('{{ $unit->id }}')">
                                                <div class="shrink-0 w-5 h-5 flex items-center justify-center bg-primary text-primary-foreground rounded-full shadow-md animate-in zoom-in-50 duration-200">
                                                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </template>
                                            <template x-if="!(selectedIds.includes({{ $unit->id }}) || selectedIds.includes('{{ $unit->id }}'))">
                                                <div class="shrink-0 w-5 h-5 rounded-full border-2 border-border group-hover:border-primary/50 transition-colors"></div>
                                            </template>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @else
                            <div class="p-8 text-center bg-muted/20 border border-dashed border-border rounded-2xl">
                                <p class="text-xs text-muted-foreground font-medium">Tidak ada unit yang sesuai dengan filter Anda.</p>
                                <button type="button" wire:click="$set('unit_search', ''); $set('selected_category_id', null);" class="text-[10px] text-primary font-bold mt-2 hover:underline">Hapus Semua Filter</button>
                            </div>
                            @endif
                        </div>

                        {{-- KERANJANG SEWA (Desktop Only) --}}
                        <div x-show="selectedIds.length > 0" class="hidden sm:block mt-8 animate-in fade-in slide-in-from-top-2 duration-300">
                            <div class="flex items-center gap-2 mb-4 px-1">
                                <div class="h-1.5 w-1.5 rounded-full bg-primary animate-pulse"></div>
                                <h3 class="text-sm font-bold text-foreground">Unit Terpilih (<span x-text="selectedIds.length"></span>)</h3>
                            </div>
                            
                            <div class="bg-muted/30 border border-border rounded-2xl overflow-hidden shadow-sm">
                                <div class="grid grid-cols-2 gap-3 p-3">
                                    <template x-for="id in selectedIds" :key="id">
                                        <div class="flex items-center justify-between p-3.5 bg-background border border-border/50 rounded-xl hover:border-primary/50 transition-all group">
                                            <div class="flex items-center gap-3">
                                                <div class="flex flex-col">
                                                    <p class="text-sm font-bold text-foreground leading-tight" x-text="unitPrices[id]?.seri || 'Unit #' + id"></p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5" x-text="(unitPrices[id]?.warna || '') + ' • ' + (unitPrices[id]?.memori || '')"></p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div class="text-right">
                                                    <p class="text-xs font-black text-primary">
                                                        Rp <span x-text="new Intl.NumberFormat('id-ID').format((duration.days * (unitPrices[id]?.day || 0)) + (duration.hours * (unitPrices[id]?.hour || 0)))"></span>
                                                    </p>
                                                    <p class="text-[9px] text-muted-foreground leading-none mt-0.5">Estimasi Subtotal</p>
                                                </div>
                                                <button type="button" @click="selectedIds = selectedIds.filter(x => x != id)"
                                                    class="p-2 text-muted-foreground hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                
                                {{-- Subtotal Footer --}}
                                <div class="bg-primary/5 p-4 border-t border-primary/10 flex justify-between items-center px-6">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-primary uppercase tracking-widest leading-none">Total Estimasi Harga</span>
                                        <span class="text-[9px] text-muted-foreground mt-1">*Harga final akan dihitung otomatis termasuk promo</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-primary" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>


                        @else
                        <div class="p-6 bg-muted/30 border border-border border-dashed rounded-2xl text-muted-foreground text-xs text-center flex flex-col items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40 text-primary"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                            Pilih jadwal sewa terlebih dahulu untuk melihat unit yang tersedia.
                        </div>
                        @endif
                    </div>
                </div>

                
                </div> <!-- END STEP 1 -->

                <!-- STEP 2: Data Diri & Promo -->
                <div x-show="step === 2" x-transition.opacity.duration.300ms x-cloak class="space-y-8 pb-8 sm:pb-0 font-sans">
<!-- 5. Data Diri -->
                <div>
                    <h2 class="text-xl font-bold tracking-tight mb-4 text-foreground">{{ (!empty($selected_unit_ids) && $waktu_mulai &&
                        $waktu_selesai) ? '4' : '3'
                        }}. Data Diri Penyewa</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none">NIK (Nomor Induk Kependudukan)</label>
                            <div class="mt-2 flex shadow-sm rounded-md h-10 w-full">
                                <input type="text" wire:model.blur="nik" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                    class="flex h-10 w-full border border-input bg-transparent rounded-l-md px-3 py-1 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring z-10"
                                    placeholder="">
                                <button type="button" wire:click="checkNik"
                                    class="inline-flex items-center justify-center rounded-r-md border border-l-0 border-input bg-muted px-4 py-2 text-xs font-semibold text-foreground hover:bg-muted/80 focus:z-10 focus:outline-none focus:ring-1 focus:ring-ring transition-colors shrink-0 whitespace-nowrap">
                                    <span wire:loading.remove wire:target="checkNik">Cek NIK</span>
                                    <span wire:loading wire:target="checkNik">Mengecek...</span>
                                </button>
                            </div>
                            @error('nik') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                            @if($nikFoundMessage)
                                <span class="text-xs {{ $nikFoundType === 'success' ? 'text-green-600 font-bold' : 'text-amber-600 font-medium' }} block mt-1">
                                    {{ $nikFoundMessage }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none">Nama Lengkap Sesuai KTP</label>
                            <input type="text" wire:model="nama"
                                x-on:input="$event.target.value = $event.target.value.toUpperCase()"
                                style="text-transform: uppercase;"
                                class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                placeholder=" ">
                            @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none">Nomor Telepon / WhatsApp</label>
                            <input type="text" wire:model="no_wa" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('no_wa') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none">Sosial Media (IG/TikTok)</label>
                            <input type="text" wire:model="sosial_media"
                                class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                placeholder="@username">
                            @error('sosial_media') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium leading-none">Alamat Domisili lengkap</label>
                            <textarea wire:model="alamat" rows="3"
                                x-on:input="$event.target.value = $event.target.value.toUpperCase()"
                                style="text-transform: uppercase;"
                                class="mt-2 flex w-full rounded-md border border-input bg-transparent px-3 py-2 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                placeholder=""></textarea>
                            @error('alamat') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                
<!-- 3. Pilih Promo (optional) -->
                @if(!empty($selected_unit_ids) && $waktu_mulai && $waktu_selesai)
                <div>
                    <h2 class="text-xl font-bold tracking-tight mb-4 text-foreground">3. Pilih Promo <span
                            class="text-sm font-normal text-muted-foreground">(Opsional)</span></h2>
                    
                    <!-- Promo & Referral Input -->
                    <div class="mb-6">
                        <label class="text-sm font-medium leading-none mb-2 block text-foreground">Punya Kode Promo atau Referral? <span class="text-xs text-muted-foreground">(Opsional)</span></label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="promo_code_input" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring uppercase text-sm font-bold placeholder:font-normal placeholder:text-[11px] placeholder:tracking-normal"
                                placeholder="MISAL: PROMO10 atau AN565">
                            <button type="button" wire:click="checkCode" 
                                class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 text-sm font-bold shadow-sm transition-colors shrink-0">
                                <span wire:loading.remove wire:target="checkCode">Apply</span>
                                <span wire:loading wire:target="checkCode">...</span>
                            </button>
                        </div>
                        @error('promo_code_input') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                        @if (session()->has('promo_message'))
                            <span class="text-xs text-green-600 font-bold block mt-1">{{ session('promo_message') }}</span>
                        @endif
                        @if($referral_code)
                            <div class="mt-2 flex items-center gap-1 text-[10px] uppercase font-bold text-sky-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Referral: {{ $referral_code }} Aktif
                            </div>
                        @endif
                    </div>

                    @if(count($available_promos) > 0)
                    <div class="grid grid-cols-1 gap-3">
                        @foreach($available_promos as $promo)
                        @php
                        $isEligible = $promo['is_eligible'] ?? true;
                        $isSelected = in_array($promo['id'], (array)($selected_promo_ids ?? []));
                        @endphp
                        <label
                            x-bind:class="selectedPromoIds.includes({{ $promo['id'] }}) || selectedPromoIds.includes('{{ $promo['id'] }}') ? 'border-primary bg-primary/10' : 'border-border bg-background'"
                            class="relative flex {{ $isEligible ? 'cursor-pointer hover:border-primary/50' : 'cursor-not-allowed opacity-50 grayscale bg-muted/20 border-border/50 shadow-inner' }} rounded-lg border-2 p-4 shadow-sm transition-all">
                            <input type="checkbox" wire:model.live="selected_promo_ids" value="{{ $promo['id'] }}"
                                class="sr-only" {{ !$isEligible ? 'disabled' : '' }}>
                            <span class="flex flex-1 items-center gap-3">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-full {{ $isEligible ? 'bg-green-500/10 text-green-600 dark:bg-green-500/20 dark:text-green-400' : 'bg-muted text-muted-foreground' }} shrink-0 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M20 12V22H4V12" />
                                        <path d="M22 7H2v5h20V7z" />
                                        <path d="M12 22V7" />
                                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
                                    </svg>
                                </span>
                                <span class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm {{ $isEligible ? 'text-foreground' : 'text-muted-foreground' }}">{{ $promo['nama_promo'] }}</span>
                                        @if($promo['can_stack'])
                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 leading-none">Gabungan OK</span>
                                        @endif
                                        @if($promo['is_hidden'])
                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 leading-none italic">KODE</span>
                                        @endif
                                    </div>
                                    <span
                                        class="text-xs {{ $isEligible ? 'text-muted-foreground' : 'text-muted-foreground/60' }}">
                                        @if($promo['tipe'] === 'diskon_persen') Diskon {{ $promo['value'] }}%
                                        @elseif($promo['tipe'] === 'diskon_nominal') Potongan Rp {{
                                        number_format($promo['value'],0,',','.') }}
                                        @elseif($promo['tipe'] === 'hari_gratis') Dapatkan +{{ $promo['value'] }} Hari
                                        Gratis (otomatis)
                                        @elseif($promo['tipe'] === 'jam_gratis') Dapatkan +{{ $promo['value'] }} Jam
                                        Gratis (otomatis)
                                        @elseif($promo['tipe'] === 'fix_price') Harga Spesial Rp {{
                                        number_format($promo['value'],0,',','.') }}
                                        @elseif($promo['tipe'] === 'cashback') Cashback Rp {{
                                        number_format($promo['value'],0,',','.') }}
                                        @endif
                                        @if(!$isEligible)
                                        <br /><span
                                            class="text-[9px] text-red-500 font-black uppercase tracking-tighter">⚠️
                                            {{ $promo['ineligible_reason'] ?? 'TIDAK MEMENUHI SYARAT DURASI' }}</span>
                                        @endif

                                        @if(isset($promo['usage_limit']) && $promo['usage_limit'] !== null)
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <div class="h-1 w-16 bg-muted rounded-full overflow-hidden">
                                                <div class="h-full bg-primary" style="width: {{ min(100, ($promo['rentals_count'] / $promo['usage_limit']) * 100) }}%"></div>
                                            </div>
                                            <span class="text-[9px] font-bold text-muted-foreground uppercase">Sisa Kuota: {{ max(0, $promo['usage_limit'] - $promo['rentals_count']) }}</span>
                                        </div>
                                        @endif
                                    </span>
                                </span>
                            </span>
                            <div x-show="selectedPromoIds.includes({{ $promo['id'] }}) || selectedPromoIds.includes('{{ $promo['id'] }}')"
                                class="bg-primary text-primary-foreground rounded-full shadow-md h-6 w-6 flex items-center justify-center shrink-0">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @else
                    <div
                        class="p-4 bg-muted border border-border border-dashed rounded-md text-muted-foreground text-sm text-center">
                        Tidak ada promo yang tersedia untuk durasi sewa ini.
                    </div>
                    @endif
                </div>
                @endif



                
                </div> <!-- END STEP 2 -->

                <!-- STEP 3: Tagihan & TNC -->
                <div x-show="step === 3" x-transition.opacity.duration.300ms x-cloak class="space-y-8">
@if(!empty($selected_unit_ids) && $waktu_mulai && $waktu_selesai)
                <div class="bg-primary/5 rounded-xl p-6 border border-primary/20 relative overflow-hidden">
                    <!-- Price Loader Overlay -->
                    <div wire:loading wire:target="calculatePrice, selected_unit_ids, selected_promo_ids" 
                        class="absolute inset-0 bg-primary/5 backdrop-blur-[1px] z-30 flex items-center justify-center">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-[10px] font-bold text-primary uppercase">Menghitung...</span>
                        </div>
                    </div>

                    <h2 class="text-[11px] sm:text-sm font-bold tracking-tight mb-4 text-foreground uppercase tracking-widest opacity-60">Rincian Pembayaran</h2>
                    <div class="space-y-2 text-[11px] sm:text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal sewa</span>
                            <span class="font-medium" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal)">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($potongan_diskon > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Diskon / promo{{ $applied_promo_label ? ' ('.$applied_promo_label.')' : '' }}</span>
                            <span class="font-medium">- Rp {{ number_format($potongan_diskon, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($hari_bonus > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Bonus hari gratis{{ $applied_promo_label ? ' ('.$applied_promo_label.')' : ''
                                }}</span>
                            <span class="font-medium">+{{ $hari_bonus }} Hari</span>
                        </div>
                        @endif
                        @if($jam_bonus > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Bonus jam gratis{{ $applied_promo_label ? ' ('.$applied_promo_label.')' : ''
                                }}</span>
                            <span class="font-medium">+{{ $jam_bonus }} Jam</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-muted-foreground">
                            <span>Kode unik <span class="text-[9px] sm:text-[10px] opacity-70">({{ $grand_total > 0 ?  : 'Otomatis' }})</span></span>
                            <span class="font-medium">+ Rp {{ $kode_unik }}</span>
                        </div>
                        <div class="pt-4 border-t border-border flex justify-between items-center">
                            <span class="font-bold text-[12px] sm:text-sm text-foreground">Grand total</span>
                            <span class="font-black text-base sm:text-lg text-primary ">Rp {{ number_format($grand_total, 0, ',',
                                '.') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                
<!-- Persetujuan Pengguna -->
                <div class="rounded-xl border border-border bg-muted/30 p-5 space-y-3">
                    <h3 class="font-semibold text-sm text-foreground">Syarat & Ketentuan Penyewaan</h3>
                    @php
                    $defaultTerms = "1. Penyewa wajib menjaga iPhone yang disewa dan bertanggung jawab atas kerusakan
                    atau kehilangan selama masa sewa.
2. Pembayaran dilakukan di awal sebelum unit diserahkan, sesuai
                    total tagihan yang tertera.
3. Keterlambatan pengembalian melewati batas toleransi waktu akan
                    dikenakan denda yang ditentukan oleh pengelola.
4. Pengelola berhak menolak penyewaan apabila
                    dokumen identitas (NIK/KTP) tidak valid atau tidak sesuai.
5. Pemesanan yang sudah terkonfirmasi
                    tidak dapat dibatalkan secara sepihak oleh penyewa.";
                    $termsRaw = \App\Models\Setting::getVal('terms_conditions', $defaultTerms);
                    $termLines = array_filter(explode("
", $termsRaw));
                    @endphp
                    <ul class="text-[10px] text-muted-foreground space-y-1.5 list-disc list-inside leading-relaxed">
                        @foreach($termLines as $line)
                        <li>{{ trim($line) }}</li>
                        @endforeach
                    </ul>
                    <label class="flex items-start gap-3 cursor-pointer mt-1">
                        <input type="checkbox" wire:model="agree" id="agree_terms"
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-primary">
                        <span class="text-xs font-medium text-foreground">Saya telah membaca dan
                            <strong>menyetujui</strong> seluruh syarat & ketentuan penyewaan di atas.</span>
                    </label>
                    @error('agree') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-12 px-8.5 font-bold text-lg disabled:opacity-70 disabled:cursor-not-allowed transition-all">
                    <span wire:loading.remove wire:target="submit">Sewa & Lanjut Pembayaran</span>
                    <div wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                        <span class="w-5 h-5 border-2 border-current border-t-transparent rounded-full animate-spin inline-block"></span>
                        <span>Memproses...</span>
                    </div>
                </button>
            
                </div> <!-- END STEP 3 -->
            </form>

    <!-- Sticky Summary & Navigation Bar (Mobile) -->
    <div x-cloak x-show="step < 3 && selectedIds.length > 0 && subtotal > 0 && !keyboardOpen" 
        wire:key="sticky-booking-summary"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-[60] sm:hidden">
        
        <!-- Backdrop/Overlay -->
        <div x-show="summaryExpanded" 
            @click="summaryExpanded = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/40 z-[-1]"></div>

        <div class="bg-background/95 backdrop-blur-md border-t border-border shadow-[0_-15px_40px_rgba(0,0,0,0.15)] transition-all duration-500 ease-in-out"
            x-bind:class="summaryExpanded ? 'rounded-t-[2.5rem]' : ''">
            <!-- Expandable Content -->
            <div x-show="summaryExpanded" 
                x-transition:enter="transition-all ease-out duration-250"
                x-transition:enter-start="max-h-0 opacity-0 translate-y-4"
                x-transition:enter-end="max-h-[60vh] opacity-100 translate-y-0"
                x-transition:leave="transition-all ease-in duration-200"
                x-transition:leave-start="max-h-[60vh] opacity-100 translate-y-0"
                x-transition:leave-end="max-h-0 opacity-0 translate-y-4"
                class="overflow-y-auto px-4 py-6 space-y-6">
                
                <!-- Unit Details -->
                <div>
                    <div class="space-y-1 max-h-[185px] overflow-y-auto pr-1 scrollbar-hide">
                        <template x-for="id in selectedIds" :key="id">
                            <div class="flex items-start justify-between py-2 border-b border-border/40 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-foreground leading-tight truncate" x-text="unitPrices[id]?.seri || 'Unit #' + id"></p>
                                    <div class="flex items-center gap-1.5 text-[9px] text-muted-foreground mt-0.5">
                                        <span x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(unitPrices[id]?.day || 0) + '/hari'"></span>
                                        <span x-show="duration.days > 0" x-text="'x ' + duration.days + ' h'"></span>
                                        <span x-show="duration.hours > 0" x-text="'+ ' + duration.hours + ' j'"></span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <p class="text-[10px] font-black text-primary">
                                        Rp <span x-text="new Intl.NumberFormat('id-ID').format((duration.days * (unitPrices[id]?.day || 0)) + (duration.hours * (unitPrices[id]?.hour || 0)))"></span>
                                    </p>
                                    <p class="text-[8px] text-muted-foreground leading-none mt-1" x-show="unitPrices[id]?.warna || unitPrices[id]?.memori">
                                        <span x-text="unitPrices[id]?.warna || ''"></span>
                                        <span x-show="unitPrices[id]?.warna && unitPrices[id]?.memori"> • </span>
                                        <span x-text="unitPrices[id]?.memori || ''"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="bg-muted/30 rounded-xl p-4 space-y-2 border border-border">
                    <div class="flex justify-between text-[11px]">
                        <span class="text-muted-foreground">Harga Sewa Dasar</span>
                        <span class="font-bold" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal)"></span>
                    </div>
                    <div x-show="$wire.potongan_diskon > 0" class="flex justify-between text-[11px] text-green-600">
                        <span>Potongan Diskon</span>
                        <span class="font-bold">- Rp <span x-text="new Intl.NumberFormat('id-ID').format($wire.potongan_diskon)"></span></span>
                    </div>
                    <div x-show="$wire.hari_bonus > 0" class="flex justify-between text-[11px] text-blue-600">
                        <span>Bonus Hari Gratis</span>
                        <span class="font-bold">+<span x-text="$wire.hari_bonus"></span> Hari</span>
                    </div>
                    <div x-show="$wire.jam_bonus > 0" class="flex justify-between text-[11px] text-purple-600">
                        <span>Bonus Jam Gratis</span>
                        <span class="font-bold">+<span x-text="$wire.jam_bonus"></span> Jam</span>
                    </div>
                    <div class="pt-2 border-t border-border flex justify-between items-center">
                        <span class="text-xs font-bold text-foreground">Total Estimasi</span>
                        <span class="text-base font-black text-primary" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, subtotal - ($wire.potongan_diskon || 0)))"></span>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar (Always Visible) -->
            <div class="px-4 py-4 flex items-center justify-between relative z-10">
                <div class="flex-1 flex flex-col gap-0.5 cursor-pointer select-none" @click="summaryExpanded = !summaryExpanded">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest" x-text="selectedIds.length + ' Unit Terpilih'"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" 
                            class="text-muted-foreground transition-transform duration-500"
                            x-bind:class="summaryExpanded ? 'rotate-180' : ''">
                            <path d="m18 15-6-6-6 6"/>
                        </svg>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-sm font-black text-primary" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, subtotal - ($wire.potongan_diskon || 0)))"></span>
                        
                        <span x-show="$wire.potongan_diskon > 0" 
                            class="inline-flex items-center rounded-full border border-transparent bg-green-500/10 px-1.5 py-0.5 text-[9px] font-bold text-green-600 dark:text-green-400">
                            -Rp<span x-text="new Intl.NumberFormat('id-ID').format($wire.potongan_diskon)"></span>
                        </span>
                        <span x-show="$wire.hari_bonus > 0" 
                            class="inline-flex items-center rounded-full border border-transparent bg-blue-500/10 px-1.5 py-0.5 text-[9px] font-bold text-blue-600 dark:text-blue-400">
                            +<span x-text="$wire.hari_bonus"></span> Hari
                        </span>
                        <span x-show="$wire.jam_bonus > 0" 
                            class="inline-flex items-center rounded-full border border-transparent bg-purple-500/10 px-1.5 py-0.5 text-[9px] font-bold text-purple-600 dark:text-purple-400">
                            +<span x-text="$wire.jam_bonus"></span> Jam
                        </span>
                    </div>
                </div>
                <button type="button" @click.prevent="nextStep()" class="bg-primary text-primary-foreground font-bold px-6 py-2.5 rounded-xl shadow-lg active:scale-95 transition-all text-sm">Lanjut</button>
            </div>

            <!-- Safe Area spacer -->
            <div class="h-[env(safe-area-inset-bottom)] w-full"></div>
        </div>
    </div>

            <!-- Desktop Navigation Buttons -->
            <div x-cloak x-show="step < 3" class="hidden sm:flex justify-end mt-6 gap-3 border-t border-border pt-6">
                <button type="button" x-show="step === 2" @click="step = 1" class="px-6 py-2 border border-border rounded-lg font-bold text-muted-foreground hover:bg-muted text-sm transition-colors">Kembali</button>
                <button type="button" @click="nextStep()" class="bg-primary text-primary-foreground font-bold px-8 py-2 rounded-lg shadow text-sm hover:bg-primary/90 transition-colors">Lanjut</button>
            </div>

            <!-- Step 3 Desktop Back Button -->
            <div x-cloak x-show="step === 3" class="mt-4 flex justify-between">
                <button type="button" @click="step = 2" class="px-6 py-2 border border-border rounded-lg font-bold text-muted-foreground hover:bg-muted text-sm transition-colors">Kembali Perbaiki Data</button>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('bookingForm', () => ({
        step: 1,
        summaryExpanded: false,
        keyboardOpen: false,
        selectedIds: @entangle('selected_unit_ids'),
        selectedPromoIds: @entangle('selected_promo_ids'),
        unitPrices: {!! $unitPricesJson !!},
        
        nextStep() {
            if (this.step === 1) {
                if (!this.selectedIds || this.selectedIds.length === 0) {
                    alert('Harap pilih jadwal dan unit terlebih dahulu.');
                    return;
                }
                this.step = 2;
                window.scrollTo({top: 0, behavior: 'smooth'});
            } else if (this.step === 2) {
                const nk = $wire.get('nik');
                const nm = $wire.get('nama');
                const wa = $wire.get('no_wa');
                const sm = $wire.get('sosial_media');
                if(!nk || !nm || !wa || !sm) {
                    alert('Harap lengkapi Data Diri (NIK, Nama, No. WhatsApp, Sosial Media) terlebih dahulu.');
                    return;
                }
                this.step = 3;
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        },
        
        get duration() {
            const startStr = $wire.waktu_mulai;
            const endStr = $wire.waktu_selesai;
            if (!startStr || !endStr) return { days: 0, hours: 0 };
            
            const start = new Date(startStr);
            const end = new Date(endStr);
            if (isNaN(start) || isNaN(end) || end <= start) return { days: 0, hours: 0 };
            
            const diffInMs = end - start;
            const diffInHours = Math.max(1, Math.floor(diffInMs / (1000 * 60 * 60)));
            return {
                days: Math.floor(diffInHours / 24),
                hours: diffInHours % 24
            };
        },

        get subtotal() {
            let total = 0;
            const dur = this.duration;
            const selected = this.selectedIds || [];
            selected.forEach(id => {
                const price = this.unitPrices[id];
                if (price) {
                    total += (dur.days * price.day) + (dur.hours * price.hour);
                }
            });
            return total;
        },

        init() {
            // Keyboard Visibility Detection
            const handleFocus = (e) => {
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    this.keyboardOpen = true;
                }
            };
            const handleBlur = () => {
                // Delay blur to prevent flickering
                setTimeout(() => {
                    const activeTag = document.activeElement.tagName;
                    if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag)) {
                        this.keyboardOpen = false;
                    }
                }, 100);
            };

            document.addEventListener('focusin', handleFocus);
            document.addEventListener('focusout', handleBlur);

            // Robust VisualViewport handling for modern iOS/Android with simple debounce
            if (window.visualViewport) {
                let resizeTimer;
                window.visualViewport.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        const isKeyboard = window.visualViewport.height < (window.innerHeight * 0.9);
                        this.keyboardOpen = isKeyboard;
                    }, 50);
                });
            }
        }
    }));

    document.addEventListener('livewire:initialized', () => {
        const storageKey = 'rentspace_booking_draft';
        let isSubmitting = false;

        // 1. Auto-Restore from LocalStorage
        const savedDraft = localStorage.getItem(storageKey);
        if (savedDraft) {
            try {
                const data = JSON.parse(savedDraft);
                Object.keys(data).forEach(key => {
                    if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
                        $wire.set(key, data[key]);
                    }
                });
                console.log('Draft pemesanan berhasil dipulihkan.');
            } catch (e) {
                console.error('Gagal memulihkan draft', e);
            }
        }

        // 2. Auto-Save to LocalStorage on component updates
        // We'll hook into Alpine's $watch or just a general update listener
        Livewire.on('component-updated', () => {
            if (!isSubmitting) saveToStorage();
        });

        // Also save on any input change to be safe
        function saveToStorage() {
            const fields = [
                'nik', 'nama', 'alamat', 'no_wa', 'sosial_media',
                'waktu_mulai', 'waktu_selesai', 
                'selected_unit_ids', 'selected_category_id'
            ];
            const data = {};
            fields.forEach(field => {
                data[field] = $wire.get(field);
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        }

        // periodic save as fallback
        setInterval(() => {
            if (!isSubmitting) saveToStorage();
        }, 5000);

        // 3. Unsaved Changes Warning
        window.addEventListener('beforeunload', (e) => {
            if (isSubmitting) return;

            const hasUnits = $wire.get('selected_unit_ids').length > 0;
            const hasData = $wire.get('nik') || $wire.get('nama');

            if (hasUnits || hasData) {
                saveToStorage();
                e.preventDefault();
                e.returnValue = ''; // Standard browser prompt
            }
        });

        // 4. Cleanup on success
        $wire.on('booking-submitted', () => {
            isSubmitting = true;
            localStorage.removeItem(storageKey);
        });
    });
</script>
@endscript