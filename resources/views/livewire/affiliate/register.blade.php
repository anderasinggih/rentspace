<div class="min-h-screen pt-12 pb-12 px-4 sm:px-6 lg:px-8 bg-background flex flex-col justify-start items-center">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-10">
            <h1
                class="text-3xl font-extrabold tracking-tight text-foreground transition-all duration-700 animate-in fade-in slide-in-from-top-4">
                Gabung Affiliator RentSpace</h1>
            <p class="mt-2 text-muted-foreground text-sm ">Dapatkan komisi dari setiap penyewaan yang Anda referensikan.</p>
        </div>

        <div class="bg-card border border-border shadow-2xl rounded-2xl overflow-hidden">
            <div class="p-8">
                <form wire:submit.prevent="register" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Account Info -->
                        <div class="space-y-4 md:col-span-2">
                            <h3 class="text-lg font-bold border-b border-border pb-2">Informasi Akun</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Nama Lengkap</label>
                                    <input type="text" wire:model="name" placeholder="Sesuai KTP" oninput="this.value = this.value.toUpperCase()"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Email</label>
                                    <input type="email" wire:model="email" placeholder="email@contoh.com"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Password</label>
                                    <input type="password" wire:model="password"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('password') <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Konfirmasi Password</label>
                                    <input type="password" wire:model="password_confirmation"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                </div>
                            </div>
                        </div>

                        <!-- Personal Info -->
                        <div class="space-y-4 md:col-span-2 pt-6">
                            <h3 class="text-lg font-bold border-b border-border pb-2">Informasi Pribadi</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">NIK (Nomor Induk
                                        Kependudukan)</label>
                                    <input type="text" wire:model="nik" placeholder="16 digit angka"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('nik') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Nomor WhatsApp</label>
                                    <input type="text" wire:model="no_hp" placeholder="08xxxxxxxxxx"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('no_hp') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="text-sm font-medium leading-none">Alamat Lengkap (Domisili)</label>
                                    <textarea wire:model="alamat" rows="2" oninput="this.value = this.value.toUpperCase()"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                                    @error('alamat') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Bank Info -->
                        <div class="space-y-4 md:col-span-2 pt-6">
                            <h3 class="text-lg font-bold border-b border-border pb-2">Informasi Pembayaran (Payout)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Nama Bank</label>
                                    <select wire:model="bank_name"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                        <option value="">Pilih Bank</option>
                                        <option value="BCA">BCA</option>
                                        <option value="BNI">BNI</option>
                                        <option value="BRI">BRI</option>
                                        <option value="Mandiri">Mandiri</option>
                                        <option value="BSI">BSI</option>
                                        <option value="DANA">DANA (E-Wallet)</option>
                                        <option value="OVO">OVO (E-Wallet)</option>
                                        <option value="Gopay">Gopay (E-Wallet)</option>
                                    </select>
                                    @error('bank_name') <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium leading-none">Nomor Rekening / HP</label>
                                    <input type="text" wire:model="bank_account_number"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('bank_account_number') <span
                                    class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="text-sm font-medium leading-none">Nama Pemilik Rekening</label>
                                    <input type="text" wire:model="bank_account_name" placeholder="Sesuai Buku Tabungan" oninput="this.value = this.value.toUpperCase()"
                                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    @error('bank_account_name') <span class="text-xs text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-8">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-foreground text-background shadow-lg hover:bg-foreground/90 h-12 px-8 font-bold text-lg transition-all active:scale-[0.98] disabled:opacity-50">
                            <span wire:loading.remove>Daftar Jadi Affiliator</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                        <p class="text-center text-[10px] text-muted-foreground mt-4">
                            Dengan mendaftar, Anda menyetujui kebijakan kemitraan RentSpace.
                        </p>
                    </div>
                </form>
            </div>

            <div class="bg-muted/50 p-6 border-t border-border flex items-center justify-center gap-2">
                <span class="text-sm text-muted-foreground">Sudah punya akun?</span>
                <a href="{{ route('affiliate.login') }}" wire:navigate
                    class="text-sm font-bold text-foreground hover:underline">Masuk di sini</a>
            </div>
        </div>
    </div>
</div>