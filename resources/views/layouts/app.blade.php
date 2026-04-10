<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
    </head>
    <body class="bg-background text-foreground antialiased font-sans flex flex-col min-h-screen">
        @unless (request()->routeIs('public.booking'))
            <livewire:navbar />
        @endunless
        <main class="flex-1 w-full flex flex-col">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
