<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" x-bind:class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPHONE RENT SPACE PURWOKERTO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased min-h-screen bg-background text-foreground flex flex-col">

    <!-- Navbar Publik Baru yang identik dengan Admin Navbar -->
    <livewire:navbar />

    <main class="flex-1 w-full">
        <!-- Hero section -->
        <section class="relative w-full overflow-hidden flex flex-col items-center text-center py-24 sm:py-36 mb-12 sm:rounded-[2rem] sm:mx-6 lg:max-w-7xl lg:mx-auto mt-0 sm:mt-6 shadow-2xl">
            <!-- Background Image -->
            <div class="absolute inset-0 z-0 bg-zinc-950">
                <img src="{{ asset('storage/hero.jpg') }}?t={{ time() }}" onerror="this.style.opacity='0.1'" class="w-full h-full object-cover opacity-60 transition-transform duration-1000 scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/50 to-transparent"></div>
                <div class="absolute inset-0 bg-black/20"></div>
            </div>

            <!-- Teks -->
            <div class="relative z-10 w-full flex flex-col items-center text-center px-4 sm:px-6 lg:px-8">
                <div class="inline-flex items-center rounded-full border border-white/20 bg-white/10 backdrop-blur-md text-white/90 px-4 py-1.5 text-xs font-semibold mb-8 cursor-default tracking-widest uppercase">
                    IPHONE RENT SPACE PURWOKERTO
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl xl:text-6xl text-white uppercase max-w-4xl drop-shadow-md">
                    {!! nl2br(e(\App\Models\Setting::getVal('home_title', "Sewa iPhone Impian Anda\nLebih Mudah & Terjangkau."))) !!}
                </h1>
                <p class="mt-6 text-lg sm:text-xl leading-8 text-zinc-300 font-medium max-w-2xl drop-shadow-sm">
                    {{ \App\Models\Setting::getVal('home_description', 'Pilihan terbaik untuk merasakan pengalaman menggunakan produk Apple original tanpa harus membeli baru. Proses cepat, stok terlihat transparan, dan langsung transaksi!') }}
                </p>
                <div class="mt-12 flex flex-col sm:flex-row items-center gap-4 w-full justify-center">
                    <a href="{{ route('public.timeline') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl font-bold transition-all bg-white text-zinc-950 shadow-[0_4px_24px_rgba(255,255,255,0.2)] hover:bg-zinc-100 hover:scale-[1.03] hover:shadow-[0_4px_32px_rgba(255,255,255,0.3)] min-w-[200px] h-12 px-8 py-2 text-base">
                        LIHAT JADWAL
                    </a>
                </div>
            </div>
        </section>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <!-- Promo -->
            @php
                $promos = \App\Models\PricingRule::where('is_active', true)->get();
            @endphp
            @if($promos->count() > 0)
                <div class="mt-16 w-full max-w-4xl text-left">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-muted-foreground mb-6 text-center">Promo Spesial Aktif</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($promos as $promo)
                        <a href="{{ route('public.booking') }}" wire:navigate class="block p-5 bg-background shadow-sm border border-border rounded-xl hover:border-primary/50 hover:shadow-md transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-bold text-foreground text-sm">{{ $promo->nama_promo }}</h3>
                                        <span class="inline-flex items-center rounded-full border border-transparent bg-primary px-2.5 py-0.5 text-xs font-semibold text-primary-foreground transition-colors hover:bg-primary/80 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">Promo Aktif</span>
                                    </div>
                                    <p class="text-xs text-muted-foreground line-clamp-2">Syarat sewa min. {{ $promo->syarat_minimal_durasi }} Jam. {{ $promo->tipe === 'diskon_persen' ? "Diskon special {$promo->value}%" : "Penyesuaian Harga Spesial" }}</p>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-border py-8 bg-muted/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-muted-foreground">
            <p class="mb-2 font-medium">{{ \App\Models\Setting::getVal('admin_address', 'Jl. Jendral Sudirman, Purwokerto') }}</p>
            &copy; {{ date('Y') }} RENT SPACE. Hak Cipta Dilindungi.
        </div>
    </footer>

    @livewireScripts
</body>

</html>