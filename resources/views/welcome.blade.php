<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
    x-bind:class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sewa iPhone</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased min-h-screen bg-background text-foreground">
        
        <livewire:navbar />

        <main class="w-full">
            <!-- Hero section -->
            <section class="max-w-6xl mx-auto px-4 py-24 sm:py-32 flex flex-col items-center text-center">
                <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-muted text-muted-foreground mb-6">
                    Mulai petualanganmu sekarang 🎉
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl text-foreground">
                    Sewa iPhone Impian,<br />Lebih Mudah & Terjangkau.
                </h1>
                <p class="mt-6 text-lg leading-8 text-muted-foreground max-w-2xl">
                    Pilihan terbaik untuk merasakan pengalaman menggunakan iPhone terbaru tanpa harus membeli. Proses cepat, aman, dan harga bersahabat.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="{{ route('public.timeline') }}" wire:navigate class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-8 py-2">
                        Lihat Katalog iPhone
                    </a>
                    <a href="/" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-muted hover:text-foreground h-10 px-8 py-2">
                        Pelajari Lebih Lanjut
                    </a>
                </div>
            </section>
        </main>
        
        @livewireScripts
    </body>
</html>
