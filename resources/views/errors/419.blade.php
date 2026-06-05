@component('layouts.app')
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-24 text-center">
        <!-- Icon -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-zinc-500/10 blur-3xl rounded-full"></div>
            <div class="relative flex items-center justify-center w-24 h-24 rounded-full bg-card border border-border shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground animate-bounce">
                    <path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <h1 class="text-4xl font-black tracking-tighter sm:text-6xl text-foreground mb-4">
            419
        </h1>
        <h2 class="text-xl font-bold text-foreground mb-3">Sesi Kedaluwarsa</h2>
        <p class="text-muted-foreground max-w-md mx-auto mb-10 leading-relaxed">
            Halaman ini sudah terlalu lama terbuka tanpa aktivitas. Untuk menjaga keamanan data Anda, silakan segarkan halaman ini sebelum melanjutkan.
        </p>

        <!-- Actions -->
        <div class="flex flex-col items-center gap-3 w-full max-w-xs">
            <button onclick="window.location.reload()" 
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-primary text-primary-foreground font-bold transition-all hover:scale-[1.02] active:scale-95 shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                Segarkan Sekarang
            </button>
            <a href="/" wire:navigate class="text-xs font-bold text-muted-foreground hover:text-foreground transition-colors uppercase tracking-widest mt-4">
                Balik ke Beranda
            </a>
        </div>
    </div>
@endcomponent
