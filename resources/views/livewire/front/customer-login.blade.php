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
                    <h1 class="text-xl font-bold text-foreground">Masuk Pelanggan</h1>
                    <p class="text-sm text-muted-foreground mt-1.5">Gunakan No. WhatsApp & Email Anda.</p>
                </div>

                {{-- Errors --}}
                @if ($errors->any())
                    <div class="mb-4 flex items-start gap-3 rounded-xl bg-red-500/10 border border-red-500/20 px-4 py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="text-red-400 shrink-0 mt-0.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" x2="12" y1="8" y2="12" />
                            <line x1="12" x2="12.01" y1="16" y2="16" />
                        </svg>
                        <div class="text-xs text-red-400 font-medium space-y-1">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form wire:submit.prevent="login" class="space-y-4">
                    {{-- WhatsApp --}}
                    <div>
                        <label for="no_wa" class="block text-xs font-semibold text-muted-foreground mb-1.5 ml-1">Nomor WhatsApp</label>
                        <div class="relative group">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <input id="no_wa" type="text" wire:model="no_wa"
                                placeholder="Ketik Nomor HP/WhatsApp..."
                                class="block w-full h-12 rounded-xl border border-input bg-background pl-11 pr-4 text-sm font-medium placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                                autocomplete="off">
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-xs font-semibold text-muted-foreground mb-1.5 ml-1">Email</label>
                        <div class="relative group">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <input id="email" type="email" wire:model="email"
                                placeholder="nama@email.com"
                                class="block w-full h-12 rounded-xl border border-input bg-background pl-11 pr-4 text-sm font-medium placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                                autocomplete="off">
                        </div>
                    </div>

                {{-- Remember Me --}}
                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="remember" wire:model="remember"
                        class="w-4 h-4 rounded-md border-input bg-background text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0 transition-all cursor-pointer accent-primary">
                    <label for="remember" class="text-xs font-medium text-muted-foreground select-none cursor-pointer">
                        Ingat Saya (24 Jam)
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold text-sm hover:bg-primary/90 transition-all active:scale-[0.98] shadow-sm mt-2"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                    <span wire:loading.remove>Masuk ke Akun</span>
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
        <div class="mt-6 text-center space-y-4">
            <p class="text-xs text-muted-foreground">
                Belum punya pesanan?
                <a href="{{ route('public.booking') }}" wire:navigate
                    class="text-primary font-bold hover:underline"> Booking sekarang</a>
            </p>
            <div class="pt-2 border-t border-border/50">
                <a href="{{ route('public.home') }}" wire:navigate class="text-[10px] font-bold text-muted-foreground/60 hover:text-foreground transition-colors">
                    ← KEMBALI KE BERANDA
                </a>
            </div>
        </div>
    </div>
</div>