<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased font-sans">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $title ?? 'Dashboard Admin' }} - {{ config('app.name', 'RentSpace') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        function applyTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        applyTheme();
        document.addEventListener('livewire:navigated', applyTheme);
    </script>
    <style>
        html,
        body {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
        }

        /* Prevent input auto-zoom on iOS */
        @media screen and (max-width: 768px) {

            input,
            select,
            textarea {
                font-size: 16px !important;
            }
        }
    </style>
    <script>
        // Force disable double-tap to zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            let now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
    <!-- Alpine.js is included via Vite/Livewire usually but handled automatically by Livewire 3 -->
</head>

<body class="bg-background min-h-screen text-foreground antialiased font-sans flex flex-col">

    <!-- Admin Sticky Top Navbar Navigation -->
    <livewire:admin.admin-navbar />

    <livewire:admin.command-palette />

    <!-- Main Content Area -->
    <main class="flex-1 w-full max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>
</body>

</html>