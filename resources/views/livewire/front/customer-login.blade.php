<div class="min-h-[100dvh] bg-background flex flex-col items-center justify-center px-4 pb-20 sm:pb-32">

    <div class="w-full max-w-sm">
        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <a href="{{ route('public.home') }}" wire:navigate class="inline-block">
                <span class="text-2xl font-extrabold tracking-tight text-foreground">RENT SPACE PURWOKERTO</span>
            </a>
        </div>

        {{-- Card --}}
        <div class="bg-card border border-border rounded-2xl shadow-lg p-8">
            <div class="mb-6 text-center">
                <h1 class="text-xl font-bold text-foreground">Masuk sebagai Pelanggan</h1>
                <p class="text-sm text-muted-foreground mt-1.5">Gunakan NIK dan No. WhatsApp yang Anda daftarkan saat
                    booking.</p>
            </div>

            {{-- Error --}}
            @if ($errors->has('nik'))
                <div class="mb-4 flex items-start gap-3 rounded-xl bg-red-500/10 border border-red-500/20 px-4 py-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="text-red-400 shrink-0 mt-0.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                    <p class="text-sm text-red-400 font-medium">{{ $errors->first('nik') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="login" class="space-y-4">
                {{-- NIK --}}
                <div>
                    <label for="nik" class="block text-xs font-semibold text-muted-foreground mb-1.5 ml-1">NIK (Nomor
                        Induk Kependudukan)</label>
                    <input id="nik" type="text" wire:model="nik" inputmode="numeric" maxlength="16"
                        placeholder="16 digit NIK Anda"
                        class="block w-full h-11 rounded-xl border border-input bg-background px-4 text-sm font-medium placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        autocomplete="off">
                </div>

                {{-- No. WA --}}
                <div>
                    <label for="no_wa" class="block text-xs font-semibold text-muted-foreground mb-1.5 ml-1">Nomor
                        WhatsApp</label>
                    <input id="no_wa" type="tel" wire:model="no_wa" inputmode="tel" placeholder="Contoh: 08123456789"
                        class="block w-full h-11 rounded-xl border border-input bg-background px-4 text-sm font-medium placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        autocomplete="off">
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="remember" wire:model="remember"
                        class="w-4 h-4 rounded border-input bg-background text-primary focus:ring-primary/50 focus:ring-offset-0 transition-colors">
                    <label for="remember" class="text-xs font-medium text-muted-foreground select-none cursor-pointer">
                        Ingat Saya (Sesi login 24 Jam)
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full h-11 rounded-xl bg-primary text-primary-foreground font-semibold text-sm hover:bg-primary/90 transition-colors shadow-sm mt-2"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                    <span wire:loading.remove>Masuk</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        Memverifikasi...
                    </span>
                </button>
            </form>
        </div>

        {{-- Footer links --}}
        <div class="mt-6 text-center space-y-2">
            <p class="text-xs text-muted-foreground">
                Belum punya pesanan?
                <a href="{{ route('public.booking') }}" wire:navigate
                    class="text-primary font-semibold hover:underline"> Booking sekarang</a>
            </p>
            <p class="text-xs text-muted-foreground">
                <a href="{{ route('public.home') }}" wire:navigate class="hover:underline text-muted-foreground/70">←
                    Kembali ke Beranda</a>
            </p>
        </div>
    </div>
</div>