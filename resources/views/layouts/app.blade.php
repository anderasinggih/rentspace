<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-background text-foreground antialiased font-sans flex flex-col min-h-screen">
        <livewire:navbar />
        <main class="flex-1 w-full flex flex-col pt-12">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
