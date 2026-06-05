@component('layouts.app')
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-24 text-center">
        <!-- Icon -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-amber-500/10 blur-3xl rounded-full"></div>
            <div class="relative flex items-center justify-center w-24 h-24 rounded-full bg-card border border-amber-500/20 shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <h1 class="text-4xl font-black tracking-tighter sm:text-6xl text-foreground mb-4">
            403
        </h1>
        <h2 class="text-xl font-bold text-foreground mb-3">Akses Ditolak</h2>
        <p class="text-muted-foreground max-w-md mx-auto mb-10 leading-relaxed">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Area ini hanya dapat diakses oleh petugas Rent Space resmi.
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full max-w-sm">
            <a href="/" wire:navigate 
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-zinc-900 text-white font-bold transition-all hover:bg-black active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Kembali ke Beranda
            </a>
            <a href="{{ route('public.check-order') }}" wire:navigate 
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-secondary text-secondary-foreground font-bold border border-border transition-all hover:bg-muted active:scale-95">
                Lihat Pesanan Saya
            </a>
        </div>

        <p class="mt-12 text-xs text-muted-foreground">
            Merasa ini sebuah kesalahan? <a href="https://wa.me/{{ \App\Models\Setting::getVal('admin_wa', '6281234567890') }}" class="font-bold text-primary hover:underline">Tanyakan Admin</a>
        </p>
    </div>
@endcomponent
