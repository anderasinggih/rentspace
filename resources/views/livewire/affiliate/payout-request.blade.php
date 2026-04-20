<div class="min-h-screen bg-background pb-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        
        {{-- Progress Header for Mobile --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-foreground">Ajukan Penarikan Komisi</h1>
                <p class="text-xs text-muted-foreground mt-1">Selesaikan 3 langkah untuk mencairkan saldo Anda.</p>
            </div>
            <a href="{{ route('affiliate.dashboard') }}" class="text-xs font-bold text-muted-foreground hover:text-foreground flex items-center gap-1 group">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:-translate-x-0.5 transition-transform"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
        </div>

        <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
            
            {{-- Step 1: Nominal --}}
            @if($payoutStep === 'amount')
                <div class="p-6 md:p-8 space-y-6">
                    <div class="p-5 rounded-lg bg-primary/5 border border-primary/10 flex justify-between items-center">
                        <div class="space-y-0.5">
                            <p class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Saldo Bisa Ditarik</p>
                            <p class="text-xl md:text-2xl font-bold text-primary tracking-tight">Rp {{ number_format($this->walletBalance, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-foreground opacity-80">Masukkan Nominal Penarikan</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">Rp</span>
                            <input type="number" wire:model.defer="payoutAmount" 
                                class="flex h-12 w-full rounded-lg border border-input bg-background pl-14 pr-4 py-2 text-base shadow-sm transition-colors focus:ring-1 focus:ring-primary outline-none"
                                placeholder="Minimal Rp 50.000">
                        </div>
                        @error('payoutAmount') <span class="text-[10px] text-red-500 font-bold italic">{{ $message }}</span> @enderror
                    </div>

                    <p class="text-[10px] text-muted-foreground leading-relaxed">
                        Dana akan ditransfer ke rekening bank yang terdaftar di profil Anda. Pastikan data sudah benar sebelum melanjutkan.
                    </p>

                    <button wire:click="nextToDetails"
                        class="w-full h-11 bg-primary text-primary-foreground rounded-lg shadow-md hover:bg-primary/95 font-bold text-sm transition-all active:scale-[0.98]">
                        Lanjutkan ke Verifikasi &rarr;
                    </button>
                </div>

            {{-- Step 2: Konfirmasi Data --}}
            @elseif($payoutStep === 'details')
                <div class="p-6 md:p-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-foreground uppercase tracking-wider">Konfirmasi Detail</h3>
                        <button wire:click="$set('payoutStep', 'amount')" class="text-xs font-bold text-primary hover:underline">Ubah Nominal</button>
                    </div>

                    <div class="divide-y divide-border border rounded-lg bg-muted/30 overflow-hidden">
                        <div class="p-4 flex justify-between items-center bg-background/50">
                            <span class="text-[10px] font-bold text-muted-foreground uppercase">Nominal Tarik</span>
                            <span class="text-sm font-bold">Rp {{ number_format($payoutAmount, 0, ',', '.') }}</span>
                        </div>
                        <div class="p-4 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-muted-foreground uppercase">Biaya Admin Bank</span>
                            <span class="text-xs font-bold text-red-500">- Rp {{ number_format(self::ADMIN_FEE, 0, ',', '.') }}</span>
                        </div>
                        <div class="p-4 flex justify-between items-center bg-primary/5">
                            <span class="text-[10px] font-bold text-primary uppercase">Total Diterima</span>
                            <span class="text-base font-black text-primary">Rp {{ number_format($payoutAmount - self::ADMIN_FEE, 0, ',', '.') }}</span>
                        </div>
                        <div class="p-4 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-muted-foreground uppercase">Bank / Wallet</span>
                            <span class="text-sm font-bold">{{ strtoupper($profile->bank_name) }}</span>
                        </div>
                        <div class="p-4 flex justify-between items-center bg-background/50">
                            <span class="text-[10px] font-bold text-muted-foreground uppercase">Atas Nama</span>
                            <span class="text-sm font-bold">{{ $profile->bank_account_name }}</span>
                        </div>
                        <div class="p-4 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-muted-foreground uppercase">Nomor Rekening</span>
                            <span class="text-sm font-bold text-primary font-mono">{{ $profile->bank_account_number }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-[10px] text-amber-700 leading-relaxed font-medium">
                        <strong>Peringatan:</strong> Kami tidak bertanggung jawab atas kegagalan transfer akibat kesalahan nomor rekening atau bank yang tidak aktif.
                    </div>

                    <button wire:click="submitPayoutRequest"
                        class="w-full h-11 bg-foreground text-background rounded-lg shadow-lg hover:opacity-90 font-bold text-sm transition-all active:scale-[0.98]">
                        Kirim Pengajuan Sekarang
                    </button>
                </div>

            {{-- Step 3: Final --}}
            @else
                <div class="p-8 md:p-12 text-center space-y-6">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-foreground"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-foreground">Pengajuan Berhasil Dikirim</h3>
                        <p class="text-[11px] text-muted-foreground leading-relaxed px-4">
                            Permintaan Payout <span class="font-bold text-foreground">#{{ $requestedPayoutId }}</span> sedang menunggu verifikasi. Mohon lakukan konfirmasi ke Admin.
                        </p>
                    </div>

                    <div class="pt-2 max-w-sm mx-auto space-y-4">
                        <a href="{{ $waLink }}" target="_blank" 
                            class="w-full h-10 bg-foreground text-background rounded-md flex items-center justify-center font-bold text-xs shadow hover:opacity-90 transition-all active:scale-[0.98]">
                            Konfirmasi via WhatsApp
                        </a>
                        <a href="{{ route('affiliate.dashboard') }}" class="block text-[10px] font-bold text-muted-foreground hover:text-foreground tracking-widest uppercase">
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Help box --}}
        <div class="mt-8 flex items-center justify-center gap-2 opacity-50">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
            <p class="text-[10px] font-medium text-muted-foreground">Butuh bantuan? Hubungi IT Support Kami.</p>
        </div>
    </div>
</div>
