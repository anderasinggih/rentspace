@component('layouts.app')
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-24 text-center">
        <!-- Icon/Illustration -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-primary/10 blur-3xl rounded-full"></div>
            <div class="relative flex items-center justify-center w-24 h-24 rounded-full bg-card border border-border shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary animate-pulse">
                    <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <h1 class="text-4xl font-black tracking-tighter sm:text-6xl text-foreground mb-4">
            404
        </h1>
        <h2 class="text-xl font-bold text-foreground mb-3">Halaman Tidak Ditemukan</h2>
        <p class="text-muted-foreground max-w-md mx-auto mb-10 leading-relaxed">
            Ups! Sepertinya Anda masuk ke link yang sudah kedaluwarsa atau salah mengetikkan alamat. Jangan khawatir, unit iPhone favorit Anda masih menunggu.
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full max-w-sm">
            <a href="/" wire:navigate 
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-primary text-primary-foreground font-bold transition-all hover:scale-[1.02] active:scale-95 shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Kembali ke Beranda
            </a>
            <a href="{{ route('public.check-order') }}" wire:navigate 
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-secondary text-secondary-foreground font-bold border border-border transition-all hover:bg-muted active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M3 10h18"/><path d="m15 19 2 2 4-4"/></svg>
                Cek Pesanan Saya
            </a>
        </div>

        <!-- Support Link -->
        <p class="mt-12 text-xs text-muted-foreground">
            Butuh bantuan? <a href="https://wa.me/{{ \App\Models\Setting::getVal('admin_wa', '6281234567890') }}" class="font-bold text-primary hover:underline">Hubungi Admin</a>
        </p>
    </div>
@endcomponent
