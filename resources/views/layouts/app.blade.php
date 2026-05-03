<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    <title>{{ $title ?? 'IPHONE RENT SPACE PURWOKERTO' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <script>
        function applyTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        // Run on initial load
        applyTheme();
        // Re-apply after Livewire 3 attribute morphs the HTML tag
        document.addEventListener('livewire:navigated', applyTheme);
    </script>

    <style>
        html,
        body {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
            overscroll-behavior-y: none;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* Allow selection in inputs */
        input,
        textarea {
            user-select: text !important;
            -webkit-user-select: text !important;
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
        // Force disable zooming
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });

        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        }, {
            passive: false
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            let now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</head>

<body class="bg-background text-foreground antialiased font-sans flex flex-col min-h-screen">
    <livewire:front.global-announcement placement="top" />

    @Unless ($hideNavbar ?? false)
    <livewire:navbar />
    @endUnless
    <main class="flex-1 w-full flex flex-col">
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>