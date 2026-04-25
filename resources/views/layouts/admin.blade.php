<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased font-sans">

<head>
    <meta charset="utf-8">
    <script>
        (function() {
            const theme = localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (theme) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
        
        function applyTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        document.addEventListener('livewire:navigated', applyTheme);
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $title ?? 'Dashboard Admin' }} - {{ config('app.name', 'RentSpace') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        
        /* Prevent Flash of Light Mode & Transitions */
        .dark { color-scheme: dark; }
        html.dark body { background-color: #09090b; } /* Same as bg-background in dark mode */
        
        /* Disable transition during page load/navigate to stop blinking */
        .no-transitions * {
            transition: none !important;
        }
    </style>
    <script>
        document.documentElement.classList.add('no-transitions');
        window.addEventListener('load', () => {
            setTimeout(() => document.documentElement.classList.remove('no-transitions'), 100);
        });
        document.addEventListener('livewire:navigating', () => {
             document.documentElement.classList.add('no-transitions');
        });
        document.addEventListener('livewire:navigated', () => {
             applyTheme();
             setTimeout(() => document.documentElement.classList.remove('no-transitions'), 100);
        });
    </script>
</head>

<body class="bg-background min-h-screen text-foreground antialiased font-sans flex flex-col">

    <livewire:admin.admin-navbar />
    <livewire:admin.command-palette />

    <main class="flex-1 w-full max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <script>
         // Final safety check at end of body
         applyTheme();
    </script>

    <script>
        window.printQrLabel = function() {
            var label = document.getElementById('qr-label');
            if (!label) {
                alert('Label QR tidak ditemukan. Silakan buka modal QR dulu.');
                return;
            }
            
            var printWindow = window.open('', '_blank', 'width=800,height=600');
            if (!printWindow) {
                alert('Popup diblokir oleh browser. Silakan izinkan popup untuk mencetak.');
                return;
            }

            var contentHtml = label.innerHTML;
            
            var html = '\x3Chtml>\x3Chead>\x3Ctitle>Print QR Label\x3C/title>';
            html += '\x3Cscript src="https://cdn.tailwindcss.com">\x3C/script>';
            html += '\x3Cstyle>';
            html += '@page { size: 80mm 80mm; margin: 0; } ';
            html += 'body { margin: 0; padding: 0; background: white; width: 80mm; height: 80mm; overflow: hidden; } ';
            html += '#qr-label-internal { width: 80mm; height: 80mm; border: none !important; padding: 4mm; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1mm; box-sizing: border-box; text-align: center; background: white; font-family: sans-serif; } ';
            html += '.print-title { font-size: 8pt; color: #666; margin: 0; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 900; } ';
            html += '.print-seri { font-size: 14pt; font-weight: 950; line-height: 1; color: black; margin: 0; max-width: 100%; overflow: hidden; } ';
            html += '.print-cat { font-size: 9pt; font-weight: bold; color: #666; margin: 0; } ';
            html += '.print-qr { width: 45mm !important; height: 45mm !important; margin: 0 auto; display: block; object-fit: contain; } ';
            html += '.print-id { font-size: 8pt; color: #999; margin-top: 0mm; font-family: monospace; font-weight: bold; }';
            html += '\x3C/style>\x3C/head>\x3Cbody>';
            html += '\x3Cdiv id="qr-label-internal">' + contentHtml + '\x3C/div>';
            html += '\x3Cscript>';
            html += 'var img = document.querySelector("img");';
            html += 'var finalize = function() { setTimeout(function() { window.print(); window.close(); }, 500); };';
            html += 'if (img && img.complete) { finalize(); } else if(img) { img.onload = finalize; img.onerror = function() { window.close(); }; } else { finalize(); }';
            html += '\x3C/script>\x3C/body>\x3C/html>';

            printWindow.document.write(html);
            printWindow.document.close();
        };
    </script>
</body>
</html>