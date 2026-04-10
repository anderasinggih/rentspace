<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
    x-bind:class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sewa iPhone Premium</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased min-h-screen bg-background text-foreground flex flex-col">
        
        <!-- Navbar Publik Baru yang identik dengan Admin Navbar -->
        <livewire:navbar />

        <main class="flex-1 w-full">
            <!-- Hero section -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 flex flex-col items-center text-center">
                <div class="inline-flex items-center rounded-full border px-4 py-1 text-sm font-medium transition-colors border-border bg-secondary text-secondary-foreground mb-8 cursor-default">
                    📱 Platform Sewa iPhone Terpercaya
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl text-foreground max-w-4xl">
                    Sewa iPhone Impian Anda<br/>
                    <span class="text-primary">Lebih Mudah & Terjangkau.</span>
                </h1>
                <p class="mt-8 text-lg leading-8 text-muted-foreground max-w-2xl mx-auto">
                    Pilihan terbaik untuk merasakan pengalaman menggunakan produk Apple original tanpa harus membeli baru. Proses cepat, stok terlihat transparan, dan langsung transaksi!
                </p>
                <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('public.timeline') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center rounded-md font-medium transition-colors bg-primary text-primary-foreground shadow-md hover:bg-primary/90 h-11 px-8 py-2">
                        Pesan Cepat / Cek Katalog
                    </a>
                </div>
            </section>
        </main>
        
        <!-- Footer -->
        <footer class="border-t border-border py-8 bg-muted/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-muted-foreground">
                &copy; {{ date('Y') }} SewaPhone. Hak Cipta Dilindungi. Sistem dikelola dengan RentSpace.
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
