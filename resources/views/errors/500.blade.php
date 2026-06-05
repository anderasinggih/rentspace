@component('layouts.app')
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-24 text-center">
        <!-- Icon -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-red-500/10 blur-3xl rounded-full"></div>
            <div class="relative flex items-center justify-center w-24 h-24 rounded-full bg-card border border-rose-500/20 shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-rose-500">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <h1 class="text-4xl font-black tracking-tighter sm:text-6xl text-foreground mb-4">
            500
        </h1>
        <h2 class="text-xl font-bold text-foreground mb-3">Terjadi Kesalahan Internal</h2>
        <p class="text-muted-foreground max-w-md mx-auto mb-10 leading-relaxed">
            Mohon maaf, server kami sedang mengalami gangguan teknis saat memproses permintaan Anda. Tim kami telah diberitahu dan sedang berusaha memperbaikinya.
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full max-w-sm">
            <button onclick="window.location.reload()" 
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-zinc-900 text-white font-bold transition-all hover:bg-black active:scale-95 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
                Segarkan Halaman
            </button>
            <a href="https://wa.me/{{ \App\Models\Setting::getVal('admin_wa', '6281234567890') }}"
               class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-emerald-600 text-white font-bold transition-all hover:bg-emerald-700 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Lapor ke Admin
            </a>
        </div>

        <p class="mt-12 text-xs text-muted-foreground">
            Lupakan sejenak, coba akses <a href="/" class="font-bold text-primary hover:underline">Beranda</a> lagi nanti.
        </p>
    </div>
@endcomponent
