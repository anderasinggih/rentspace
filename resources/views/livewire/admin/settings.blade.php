<div>
    <div class="mb-6 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Pengaturan Sistem</h1>
                <p class="mt-1 text-sm text-muted-foreground">Konfigurasi website dan data sistem.</p>
            </div>
        </div>

        <!-- Tab Navigation (Segmented UI) -->
        <div class="p-1.5 bg-muted/40 rounded-xl flex items-center w-full gap-1 border border-border/50">
            <button wire:click="$set('activeTab', 'akun')"
                title="Akun & Affiliate"
                class="flex-1 inline-flex items-center justify-center rounded-lg py-2 text-sm font-medium transition-all
                {{ $activeTab === 'akun' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </button>
            <button wire:click="$set('activeTab', 'tampilan')"
                title="Tampilan Beranda"
                class="flex-1 inline-flex items-center justify-center rounded-lg py-2 text-sm font-medium transition-all
                {{ $activeTab === 'tampilan' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            </button>
            <button wire:click="$set('activeTab', 'umum')"
                title="Pengaturan Umum"
                class="flex-1 inline-flex items-center justify-center rounded-lg py-2 text-sm font-medium transition-all
                {{ $activeTab === 'umum' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button wire:click="$set('activeTab', 'faq')"
                title="FAQ / Tentang"
                class="flex-1 inline-flex items-center justify-center rounded-lg py-2 text-sm font-medium transition-all
                {{ $activeTab === 'faq' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
            </button>
            <button wire:click="$set('activeTab', 'database')"
                title="Backup Data"
                class="flex-1 inline-flex items-center justify-center rounded-lg py-2 text-sm font-medium transition-all
                {{ $activeTab === 'database' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        @if($activeTab === 'tampilan')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- QRIS Setting -->
                <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-border bg-muted/30">
                        <h2 class="text-lg font-semibold">QRIS Pembayaran</h2>
                        <p class="text-xs text-muted-foreground">Unggah barcode / gambar QRIS yang akan ditampilkan di halaman
                            pembayaran.</p>
                    </div>
                    <div class="p-4">
                        @if (session()->has('message'))
                            <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200"
                                role="alert">
                                {{ session('message') }}
                            </div>
                        @endif

                        <div x-data="{ photoName: null, photoPreview: null }" class="w-full">
                            <div class="mb-4 flex items-center justify-center">
                                <div
                                    class="w-48 h-48 bg-muted border border-dashed border-border rounded-lg flex items-center justify-center relative overflow-hidden">
                                    <!-- New Photo Preview -->
                                    <div x-show="photoPreview" style="display: none;" class="absolute inset-0 z-20">
                                        <span class="block w-full h-full bg-cover bg-no-repeat bg-center"
                                            x-bind:style="'background-image: url(\'' + photoPreview + '\');'"></span>
                                    </div>

                                    <!-- Current Photo -->
                                    <div x-show="!photoPreview" class="absolute inset-0 z-10">
                                        <img src="/uploads/{{ $qris }}?t={{ time() }}"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            class="w-full h-full object-cover">
                                        <div style="display:none;"
                                            class="w-full h-full flex items-center justify-center bg-muted text-muted-foreground text-xs font-medium">
                                            Logo Kosong</div>
                                    </div>
                                </div>
                            </div>

                            <form wire:submit="saveQris" class="flex flex-col gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Unggah Foto (Rekomendasi 1:1, Max 2MB)</label>
                                    <input type="file" x-ref="photo" x-on:change="
                photoName = $refs.photo.files[0].name;
                const reader = new FileReader();
                reader.onload = (e) => { photoPreview = e.target.result; };
                reader.readAsDataURL($refs.photo.files[0]);
            " wire:model="qris_photo" accept="image/*"
                                        class="w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">

                                    <div wire:loading wire:target="qris_photo"
                                        class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1 animate-pulse">
                                        Sedang memproses file ke server... Jangan klik simpan dulu.
                                    </div>
                                    @error('qris_photo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                @if(auth()->user()->role === 'admin')
                                <button type="submit" wire:confirm="Simpan foto QRIS baru ini?"
                                    class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveQris">Simpan QRIS Baru</span>
                                    <span wire:loading wire:target="saveQris">Menyimpan...</span>
                                </button>
                                @else
                                <div class="w-full h-9 flex items-center justify-center rounded-md border border-dashed border-border text-[10px] font-bold text-muted-foreground uppercase opacity-50">Mode Viewer (Read Only)</div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Hero Photo Setting -->
                <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-border bg-muted/30">
                        <h2 class="text-lg font-semibold">1:1 Foto Beranda (Hero)</h2>
                        <p class="text-xs text-muted-foreground">Unggah foto beranda (hero) yang akan ditampilkan di halaman
                            awal web.</p>
                    </div>
                    <div class="p-4">
                        <div x-data="{ heroName: null, heroPreview: null }" class="w-full">
                            <div class="mb-4 flex items-center justify-center">
                                <div
                                    class="w-48 h-48 bg-muted border border-dashed border-border rounded-lg flex items-center justify-center relative overflow-hidden">
                                    <!-- New Photo Preview -->
                                    <div x-show="heroPreview" style="display: none;" class="absolute inset-0 z-20">
                                        <span class="block w-full h-full bg-cover bg-no-repeat bg-center"
                                            x-bind:style="'background-image: url(\'' + heroPreview + '\');'"></span>
                                    </div>

                                    <!-- Current Photo -->
                                    <div x-show="!heroPreview" class="absolute inset-0 z-10">
                                        <img src="/uploads/{{ $hero }}?t={{ time() }}"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            class="w-full h-full object-cover">
                                        <div style="display:none;"
                                            class="w-full h-full flex items-center justify-center bg-muted text-muted-foreground text-xs font-medium">
                                            Logo Kosong</div>
                                    </div>
                                </div>
                            </div>

                            <form wire:submit="saveHero" class="flex flex-col gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Unggah Foto (Rekomendasi 1:1, Max 2MB)</label>
                                    <input type="file" x-ref="heroInput" x-on:change="
                heroName = $refs.heroInput.files[0].name;
                const reader = new FileReader();
                reader.onload = (e) => { heroPreview = e.target.result; };
                reader.readAsDataURL($refs.heroInput.files[0]);
            " wire:model="hero_photo" accept="image/*"
                                        class="w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">

                                    <div wire:loading wire:target="hero_photo"
                                        class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1 animate-pulse">
                                        Sedang memproses file ke server... Jangan klik simpan dulu.
                                    </div>
                                    @error('hero_photo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                @if(auth()->user()->role === 'admin')
                                <button type="submit" wire:confirm="Ganti foto beranda utama?"
                                    class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveHero">Simpan Foto Beranda</span>
                                    <span wire:loading wire:target="saveHero">Menyimpan...</span>
                                </button>
                                @else
                                <div class="w-full h-9 flex items-center justify-center rounded-md border border-dashed border-border text-[10px] font-bold text-muted-foreground uppercase opacity-50">Mode Viewer (Read Only)</div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'akun')
            <!-- Kelola Akun -->
            <div
                class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
                <div class="p-4 border-b border-border bg-muted/30">
                    <h2 class="text-lg font-semibold">{{ $isEditMode ? 'Edit Akun: ' . $name : 'Kelola Akun' }}</h2>
                    <p class="text-xs text-muted-foreground">
                        {{ $isEditMode ? 'Ubah informasi akun atau reset password.' : 'Tambah atau hapus akses masuk dasbor admin.' }}
                        {{
        auth()->user()->role !== 'admin' ? 'Fitur dikunci untuk Viewer.' : '' }}</p>
                </div>
                <div class="p-4 flex-1">
                    @if (session()->has('user_message'))
                                <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{
                        session('user_message') }}</div>
                    @endif
                    @if (session()->has('user_error'))
                                <div class="p-3 mb-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{
                        session('user_error') }}</div>
                    @endif

                    @if(auth()->user()->role === 'admin')
                        <form wire:submit="{{ $isEditMode ? 'updateUser' : 'createUser' }}"
                            class="mb-6 space-y-4 p-4 border rounded-xl bg-muted/20">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-muted-foreground mb-1">Nama Lengkap</label>
                                    <input type="text" wire:model="name" placeholder="Misal: Budi Santoso"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('name') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-muted-foreground mb-1">Alamat Email</label>
                                    <input type="email" wire:model="email" placeholder="email@contoh.com"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('email') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-muted-foreground mb-1">Password
                                        {{ $isEditMode ? '(Kosongkan jika tidak diubah)' : '' }}</label>
                                    <input type="password" wire:model="password"
                                        placeholder="{{ $isEditMode ? '••••••••' : 'Password' }}"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('password') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-muted-foreground mb-1">Peran (Role)</label>
                                    <select wire:model.live="role"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                        <option value="admin">Admin</option>
                                        <option value="staff">Staff (Operasional)</option>
                                        <option value="viewer">Viewer (View Only)</option>
                                    </select>
                                    @error('role') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center space-x-2">
                                    <button type="button"
                                        wire:click="$set('is_also_affiliate', {{ !$is_also_affiliate ? 'true' : 'false' }})"
                                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background {{ $is_also_affiliate ? 'bg-primary' : 'bg-input' }}">
                                        <span
                                            class="pointer-events-none block h-4 w-4 rounded-full bg-background shadow-lg ring-0 transition-transform {{ $is_also_affiliate ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                    <span class="text-sm font-medium text-foreground italic flex items-center gap-1.5">
                                        Aktifkan sebagai Affiliator
                                        @if($is_also_affiliate)
                                            <span
                                                class="text-[10px] bg-primary/10 text-primary px-1.5 py-0.5 rounded-full font-bold">Aktif</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            @if($role === 'affiliator' || $is_also_affiliate)
                                <div class="mt-4 p-4 rounded-lg bg-primary/5 border border-primary/10 space-y-4">
                                    <h4 class="text-[11px] font-black tracking-widest text-primary">Detail Profil Affiliator</h4>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-muted-foreground mb-1">Kode
                                                Referral</label>
                                            <input type="text" wire:model="affiliate_referral_code" placeholder="CONTOH: AB123"
                                                class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm font-mono uppercase focus-visible:ring-primary">
                                            @error('affiliate_referral_code') <span
                                            class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-muted-foreground mb-1">Komisi (%)</label>
                                            <input type="number" wire:model="affiliate_commission_rate" min="0" max="100"
                                                class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus-visible:ring-primary">
                                            @error('affiliate_commission_rate') <span
                                            class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-muted-foreground mb-1">WhatsApp
                                                (628...)</label>
                                            <input type="text" wire:model="affiliate_no_hp" placeholder="628..."
                                                class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus-visible:ring-primary">
                                            @error('affiliate_no_hp') <span class="text-red-500 text-[10px]">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-muted-foreground mb-1">NIK (KTP)</label>
                                            <input type="text" wire:model="affiliate_nik" placeholder="320..."
                                                class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus-visible:ring-primary">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-muted-foreground mb-1">Alamat
                                                Tinggal</label>
                                            <input type="text" wire:model="affiliate_alamat" placeholder="Jl. Merdeka..."
                                                class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus-visible:ring-primary">
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-primary/10">
                                        <h5 class="text-[10px] font-bold text-muted-foreground mb-3 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <rect width="20" height="14" x="2" y="5" rx="2" />
                                                <line x1="2" x2="22" y1="10" y2="10" />
                                            </svg>
                                            Informasi Rekening Payout
                                        </h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <input type="text" wire:model="affiliate_bank_name"
                                                    placeholder="Nama Bank (BCA, Mandiri, dll)"
                                                    class="h-8 w-full rounded-md border border-input bg-background px-2 py-1 text-xs focus-visible:ring-primary">
                                            </div>
                                            <div>
                                                <input type="text" wire:model="affiliate_bank_account_number"
                                                    placeholder="Nomor Rekening"
                                                    class="h-8 w-full rounded-md border border-input bg-background px-2 py-1 text-xs focus-visible:ring-primary">
                                            </div>
                                            <div>
                                                <input type="text" wire:model="affiliate_bank_account_name"
                                                    placeholder="Nama Pemilik Rekening"
                                                    class="h-8 w-full rounded-md border border-input bg-background px-2 py-1 text-xs focus-visible:ring-primary">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-2">
                                <button type="submit"
                                    wire:confirm="{{ $isEditMode ? 'Simpan perubahan data akun ini?' : 'Tambah akun baru?' }}"
                                    class="h-9 flex-1 rounded-md bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 text-sm font-bold transition-all">
                                    {{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Akun' }}
                                </button>
                                @if($isEditMode)
                                    <button type="button" wire:click="cancelEdit"
                                        wire:confirm="Batalkan pengeditan? Data yang diubah tidak akan disimpan."
                                        class="h-9 px-4 rounded-md border border-input bg-background text-sm font-medium hover:bg-accent transition-colors">
                                        Batal
                                    </button>
                                @endif
                            </div>
                        </form>
                    @endif

                    <div class="mb-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="relative w-full md:w-64 group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="text" 
                                class="block w-full pl-9 pr-3 h-8 border border-border rounded-lg bg-background text-xs placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all shadow-sm" 
                                placeholder="Cari admin/staff...">
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest leading-none">Rows</label>
                            <select wire:model.live="perPage" class="h-8 rounded-lg border border-border bg-background px-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="border rounded-lg overflow-hidden bg-background shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left shadow-sm">
                                <thead
                                    class="bg-muted/50 text-xs text-muted-foreground border-b border-border transition-colors">
                                    <tr>
                                        <th wire:click="sortBy('name')"
                                            class="px-4 py-3 font-medium cursor-pointer hover:bg-muted/50 group">
                                            <div class="flex items-center gap-1">
                                                Nama & Email
                                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                    @if($sortField === 'name')
                                                        @if($sortDirection === 'asc')
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-arrow-up">
                                                                <path d="m5 12 7-7 7 7" />
                                                                <path d="M12 19V5" />
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-arrow-down">
                                                                <path d="M12 5v14" />
                                                                <path d="m19 12-7 7-7-7" />
                                                            </svg>
                                                        @endif
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-chevrons-up-down">
                                                            <path d="m7 15 5 5 5-5" />
                                                            <path d="m7 9 5-5 5 5" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </div>
                                        </th>
                                        <th wire:click="sortBy('role')"
                                            class="px-4 py-3 font-medium cursor-pointer hover:bg-muted/50 group">
                                            <div class="flex items-center gap-1">
                                                Peran
                                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                    @if($sortField === 'role')
                                                        @if($sortDirection === 'asc')
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-arrow-up">
                                                                <path d="m5 12 7-7 7 7" />
                                                                <path d="M12 19V5" />
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-arrow-down">
                                                                <path d="M12 5v14" />
                                                                <path d="m19 12-7 7-7-7" />
                                                            </svg>
                                                        @endif
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-chevrons-up-down">
                                                            <path d="m7 15 5 5 5-5" />
                                                            <path d="m7 9 5-5 5 5" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </div>
                                        </th>
                                        <th wire:click="sortBy('created_at')"
                                            class="px-4 py-3 font-medium cursor-pointer hover:bg-muted/50 group hidden sm:table-cell">
                                            <div class="flex items-center gap-1">
                                                Tgl Terdaftar
                                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                    @if($sortField === 'created_at')
                                                        @if($sortDirection === 'asc')
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-arrow-up">
                                                                <path d="m5 12 7-7 7 7" />
                                                                <path d="M12 19V5" />
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-arrow-down">
                                                                <path d="M12 5v14" />
                                                                <path d="m19 12-7 7-7-7" />
                                                            </svg>
                                                        @endif
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-chevrons-up-down">
                                                            <path d="m7 15 5 5 5-5" />
                                                            <path d="m7 9 5-5 5 5" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-right"><span class="sr-only">Aksi</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($users as $user)
                                        <tr class="hover:bg-muted/30 {{ $editingUserId == $user->id ? 'bg-primary/5' : '' }}">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-foreground">{{ $user->name }}</div>
                                                <div class="text-[11px] text-muted-foreground">{{ $user->email }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap gap-1.5">
                                                    @if($user->role === 'admin')
                                                        <x-ui.badge variant="purple">Admin</x-ui.badge>
                                                    @elseif($user->role === 'staff')
                                                        <x-ui.badge variant="orange">Staff</x-ui.badge>
                                                    @elseif($user->role === 'viewer')
                                                        <x-ui.badge variant="zinc">Viewer</x-ui.badge>
                                                    @endif

                                                    @if($user->affiliateProfile)
                                                        <x-ui.badge variant="blue">Affiliator</x-ui.badge>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-muted-foreground hidden sm:table-cell">
                                                {{ $user->created_at->format('d/m/y') }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-3">
                                                    @if(auth()->user()->role === 'admin')
                                                        <button wire:click="editUser({{ $user->id }})"
                                                            class="text-xs text-primary hover:underline">Edit</button>

                                                    @if(auth()->id() !== $user->id)
                                                        <button wire:click="deleteUser({{ $user->id }})"
                                                            wire:confirm="Hapus admin ini?"
                                                            class="text-xs text-red-500 hover:text-red-700 hover:underline">Hapus</button>
                                                    @else
                                                        <span
                                                            class="text-[10px] text-muted-foreground italic bg-muted px-1.5 rounded">Anda</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                               <div class="p-3 border-t border-border">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 px-2">
                             <!-- Left: Rows & Info -->
                             <div class="flex items-center gap-6 order-2 md:order-1">
                                <div class="flex items-center gap-2">
                                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none">Rows</label>
                                    <select wire:model.live="perPage" class="h-8 rounded-lg border border-border bg-background px-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all shadow-sm uppercase">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                                <div class="hidden sm:block">
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest leading-none opacity-70">
                                        Total {{ $users->total() }} results
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Navigation -->
                            <div class="flex items-center gap-3 order-1 md:order-2">
                                <button wire:click="previousPage" @disabled($users->onFirstPage())
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                
                                <div class="flex items-center gap-2 px-3 h-8 bg-muted/50 rounded-lg border border-border/50">
                                    <span class="text-xs font-black text-foreground">{{ $users->currentPage() }}</span>
                                    <span class="text-[10px] font-bold text-muted-foreground uppercase opacity-50">/</span>
                                    <span class="text-xs font-black text-foreground">{{ $users->lastPage() }}</span>
                                </div>

                                <button wire:click="nextPage" @disabled(!$users->hasMorePages())
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            </div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'umum')
            <!-- Pengaturan Umum -->
            <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
                <div class="p-4 border-b border-border bg-muted/30">
                    <h2 class="text-lg font-semibold">Pengaturan Umum</h2>
                    <p class="text-xs text-muted-foreground">Konfigurasi teks beranda dan toleransi keterlambatan denda.</p>
                </div>
                <div class="p-4 space-y-6">
                    @if (session()->has('general_message'))
                        <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">
                            {{ session('general_message') }}
                        </div>
                    @endif

                    <!-- Mode Pemeliharaan (Maintenance Mode) -->
                    <div class="p-4 border border-destructive/20 bg-destructive/5 rounded-xl dark:border-destructive/30 dark:bg-destructive/10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-destructive/10 text-destructive rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">Mode Pemeliharaan (Maintenance Mode)</h3>
                                    <p class="text-[10px] text-muted-foreground/80 lowercase">Aktifkan untuk menutup akses publik sementara.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center {{ auth()->user()->role !== 'admin' ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}">
                                <input type="checkbox" wire:model.live="is_maintenance" class="sr-only peer" {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}>
                                <div
                                    class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-destructive">
                                </div>
                            </label>
                        </div>

                        @if($is_maintenance)
                            <div class="space-y-2 animate-in fade-in slide-in-from-top-2 duration-300">
                                <label class="block text-xs font-bold uppercase text-muted-foreground tracking-wider">Pesan Pemeliharaan</label>
                                <textarea wire:model.live.debounce.1000ms="maintenance_message" rows="3"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Masukkan pesan yang akan dilihat pengunjung..."></textarea>
                            </div>
                        @endif
                    </div>

                    <form wire:submit="saveGeneralSettings" class="flex flex-col gap-4 max-w-2xl">
                        <div>
                            <label class="block text-sm font-medium mb-1">Judul Beranda (Mendukung Enter)</label>
                            <textarea wire:model="home_title" rows="2"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Deskripsi Beranda</label>
                            <textarea wire:model="home_description" rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Batas Toleransi Keterlambatan Pengembalian
                                (Menit)</label>
                            <input type="number" wire:model="late_tolerance_minutes"
                                class="h-9 w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <p class="text-xs text-muted-foreground mt-1">Jika *customer* telat melebihi batas menit yang
                                ditentukan setelah jadwal selesai, Anda akan diminta memasukkan opsi Denda saat
                                menyelesai transaksi.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Minimal Penarikan Komisi Affiliate (Rp)</label>
                            <input type="number" wire:model="min_payout"
                                class="h-9 w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <p class="text-xs text-muted-foreground mt-1">Jumlah minimum saldo komisi yang harus dimiliki
                                mitra sebelum mereka dapat mengajukan penarikan dana.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Nomor WhatsApp Pelayanan Admin (format
                                628...)</label>
                            <input type="text" wire:model="admin_wa"
                                class="h-9 w-full sm:w-1/2 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Alamat Offline Toko (tampil di Footer Web
                                Publik)</label>
                            <textarea wire:model="admin_address" rows="2"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Metode Pembayaran Aktif <span
                                    class="text-xs text-muted-foreground">(Ditampilkan kepada penyewa saat
                                    checkout)</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label
                                    class="flex items-center gap-3 p-3 rounded-lg border border-input bg-background cursor-pointer hover:bg-muted/40 transition-colors">
                                    <input type="checkbox" wire:model="payment_methods.qris"
                                        class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                    <div>
                                        <p class="text-sm font-medium">QRIS</p>
                                        <p class="text-xs text-muted-foreground">Scan QR bayar langsung</p>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center gap-3 p-3 rounded-lg border border-input bg-background cursor-pointer hover:bg-muted/40 transition-colors">
                                    <input type="checkbox" wire:model="payment_methods.cash"
                                        class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                    <div>
                                        <p class="text-sm font-medium">Tunai (Cash)</p>
                                        <p class="text-xs text-muted-foreground">Bayar langsung di tempat</p>
                                    </div>
                                </label>
                                @foreach(['bca' => 'BCA', 'mandiri' => 'Mandiri', 'bni' => 'BNI', 'bri' => 'BRI', 'permata' => 'Permata', 'bsi' => 'BSI', 'cimb' => 'CIMB'] as $id => $label)
                                    <label
                                        class="flex items-center gap-3 p-3 rounded-lg border border-input bg-background cursor-pointer hover:bg-muted/40 transition-colors">
                                        <input type="checkbox" wire:model="payment_methods.{{ $id }}"
                                            class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                        <div>
                                            <p class="text-sm font-medium">{{ $label }}</p>
                                            <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-tighter">Otomatis / VA</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Link Media Sosial <span
                                    class="text-xs text-muted-foreground">(Tampil di Footer)</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Instagram
                                        URL</label>
                                    <input type="url" wire:model="social_ig_url"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="https://instagram.com/namaakun">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Nama
                                        Akun IG</label>
                                    <input type="text" wire:model="social_ig_name"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="@namaakun">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">TikTok
                                        URL</label>
                                    <input type="url" wire:model="social_tiktok_url"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="https://tiktok.com/@namaakun">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Nama
                                        Akun TikTok</label>
                                    <input type="text" wire:model="social_tiktok_name"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="@namaakun">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Syarat & Ketentuan Penyewaan <span
                                    class="text-xs text-muted-foreground">(Ditampilkan di form booking, satu baris = satu
                                    poin)</span></label>
                            <textarea wire:model="terms_conditions" rows="8"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono"
                                placeholder="1. Penyewa wajib..."></textarea>
                        </div>
                        @if(auth()->user()->role === 'admin')
                        <button type="submit" wire:confirm="Simpan perubahan pengaturan umum?"
                            class="mt-4 self-start inline-flex items-center justify-center rounded-md bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveGeneralSettings">Simpan Pengaturan</span>
                            <span wire:loading wire:target="saveGeneralSettings">Menyimpan...</span>
                        </button>
                        @endif
                    </form>
                </div>
            </div>

            @if(auth()->user()->role === 'admin')
            <div class="mt-6 bg-background rounded-xl border border-border overflow-hidden shadow-sm">
                <div class="p-4 border-b border-border bg-amber-500/5">
                    <h2 class="text-sm font-bold flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="m12 14 4-4"/><path d="m3 3 3 3"/><path d="m21 3-3 3"/><path d="M21 21-3-3"/><path d="m3 21 3-3"/><polyline points="15 6 9 6 9 12"/><path d="M12 12V6"/></svg>
                        Akses Cepat Fitur Tersembunyi (Khusus Admin)
                    </h2>
                </div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('admin.campaign') }}" 
                        class="group p-4 rounded-xl border border-border hover:border-primary/50 hover:bg-primary/5 transition-all flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-primary/10 text-primary rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.1 12.9a1.9 1.9 0 0 0 0 2.2l3 3.2a2 2 0 0 0 3 0l3-3.2a1.9 1.9 0 0 0 0-2.2L14.4 9a2 2 0 0 0-3 0Z"/><path d="M12 12V3"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold">Campaign Manager</h3>
                                <p class="text-[10px] text-muted-foreground uppercase leading-none mt-1">Kelola Pengumuman Web</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </a>

                    <a href="{{ route('admin.staff-logs') }}" 
                        class="group p-4 rounded-xl border border-border hover:border-amber-500/50 hover:bg-amber-500/5 transition-all flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-amber-500/10 text-amber-500 rounded-lg group-hover:bg-amber-500 group-hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold">Staff Activity Logs</h3>
                                <p class="text-[10px] text-muted-foreground uppercase leading-none mt-1">Audit Trail & Tracking</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
            </div>
            @endif
        @endif

        @if($activeTab === 'faq')
            <!-- Kelola FAQ Halaman Tentang -->
            <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm md:col-span-2">
                <div class="p-4 border-b border-border bg-muted/30">
                    <h2 class="text-lg font-semibold">Kelola FAQ Halaman Tentang</h2>
                    <p class="text-xs text-muted-foreground">Tambah atau edit tanya jawab yang akan muncul di halaman
                        "Tentang Kami".</p>
                </div>
                <div class="p-4">
                    @if (session()->has('faq_message'))
                                <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{
                        session('faq_message') }}</div>
                    @endif

                    <div class="space-y-4">
                        @foreach($about_faq_items as $index => $faq)
                            <div class="p-4 border border-border rounded-lg bg-muted/10 relative group">
                                @if(auth()->user()->role === 'admin')
                                <button type="button" wire:click="removeFaq({{ $index }})"
                                    class="absolute top-2 right-2 p-1 text-muted-foreground hover:text-red-500 hover:bg-red-50 rounded-md transition-all opacity-0 group-hover:opacity-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                </button>
                                @endif
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-muted-foreground mb-1">Pertanyaan
                                            #{{ $index + 1 }}</label>
                                        <input type="text" wire:model="about_faq_items.{{ $index }}.question"
                                            class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                            placeholder="Misal: Berapa lama waktu sewa?">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-bold uppercase text-muted-foreground mb-1">Jawaban</label>
                                        <textarea wire:model="about_faq_items.{{ $index }}.answer" rows="3"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                            placeholder="Masukkan jawaban di sini..."></textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if(count($about_faq_items) === 0)
                            <div class="text-center py-8 border-2 border-dashed border-border rounded-xl">
                                <p class="text-sm text-muted-foreground">Belum ada FAQ yang ditambahkan.</p>
                            </div>
                        @endif

                        @if(auth()->user()->role === 'admin')
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-border">
                            <button type="button" wire:click="addFaq"
                                class="inline-flex items-center justify-center rounded-md border border-input bg-background shadow-sm hover:bg-muted h-9 px-4 py-2 text-sm font-medium transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <path d="M5 12h14" />
                                    <path d="M12 5v14" />
                                </svg>
                                Tambah Item FAQ
                            </button>
                            <button type="button" wire:click="saveFaqSettings" wire:confirm="Simpan seluruh perubahan FAQ?"
                                class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-6 py-2 text-sm font-bold transition-colors">
                                Simpan Perubahan FAQ
                            </button>
                        </div>
                        @else
                        <div class="pt-4 border-t border-border">
                             <div class="p-3 text-center rounded-lg border border-dashed border-border text-xs font-bold text-muted-foreground uppercase opacity-50">Fitur Kelola FAQ hanya tersedia untuk Admin</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'database')
            <!-- Sistem Cadangan & Pemulihan -->
            <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm md:col-span-2">
                <div class="p-4 border-b border-border bg-muted/30">
                    <h2 class="text-lg font-semibold">Sistem Cadangan & Pemulihan</h2>
                    <p class="text-xs text-muted-foreground">Ekspor semua data sistem (Unit, Promo, Transaksi, Pengaturan)
                        ke file JSON atau pulihkan dari file cadangan.</p>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Export Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold uppercase tracking-tight text-foreground">Ekspor Data (Backup)</h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">Gunakan fitur ini untuk mendownload seluruh
                            data sistem Anda saat ini. Simpan file ini di tempat yang aman sebagai cadangan.</p>
                        @if(auth()->user()->role === 'admin')
                        <button wire:click="exportData"
                            class="inline-flex items-center justify-center rounded-md bg-sky-600 text-white shadow hover:bg-sky-700 h-10 px-6 text-sm font-bold transition-colors w-full sm:w-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="mr-2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" x2="12" y1="15" y2="3" />
                            </svg>
                            Ekspor ke .JSON
                        </button>
                        @else
                        <div class="p-3 text-center rounded-lg border border-dashed border-border text-xs font-bold text-muted-foreground uppercase opacity-50">Backup Terkunci untuk Viewer</div>
                        @endif
                    </div>

                    <!-- Import Section -->
                    <div class="space-y-4 border-t md:border-t-0 md:border-l border-border pt-6 md:pt-0 md:pl-8">
                        <h3 class="text-sm font-bold uppercase tracking-tight text-foreground">Pulihkan Data (Restore)</h3>
                        <p class="text-xs text-red-500 font-semibold leading-relaxed">PERINGATAN: Mengimpor data akan
                            menimpa data yang ada saat ini. Pastikan file backup valid dan sesuai.</p>

                        @if (session()->has('import_message'))
                            <div class="p-3 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">
                                {{ session('import_message') }}
                            </div>
                        @endif
                        @if (session()->has('import_error'))
                            <div class="p-3 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">
                                {{ session('import_error') }}
                            </div>
                        @endif

                        @if(auth()->user()->role === 'admin')
                        <form wire:submit="importData" class="space-y-3">
                        @else
                        <div class="p-3 text-center rounded-lg border border-dashed border-border text-xs font-bold text-muted-foreground uppercase opacity-50">Restore Terkunci untuk Viewer</div>
                        <div class="hidden">
                        @endif
                            <div class="relative">
                                <input type="file" wire:model="importFile" accept=".json"
                                    class="w-full text-xs text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-red-100 file:text-red-600 hover:file:bg-red-200 transition-colors">
                                <div wire:loading wire:target="importFile"
                                    class="text-[10px] text-red-600 dark:text-red-400 animate-pulse mt-1">Mengunggah file...</div>
                            </div>
                            @error('importFile') <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror

                            <button type="submit" wire:click="importData"
                                wire:confirm="Apakah Anda yakin ingin melakukan import? Data yang ada saat ini akan ditimpa."
                                class="inline-flex items-center justify-center rounded-md bg-red-600 text-white shadow hover:bg-red-700 h-10 px-6 text-sm font-bold transition-colors w-full sm:w-auto"
                                wire:loading.attr="disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" x2="12" y1="3" y2="15" />
                                </svg>
                                <span wire:loading.remove wire:target="importData">Pulihkan Data</span>
                                <span wire:loading wire:target="importData">Memproses...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div></div>
