<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased font-sans">

<head>
    <meta charset="utf-8">
    <script>
        (function() {
            // Check theme: if explicitly 'light', use light; if 'dark', use dark; otherwise default to dark
            const isLight = localStorage.theme === 'light' || localStorage.getItem('theme') === 'light';
            if (isLight) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
        
        function applyTheme() {
            const isLight = localStorage.theme === 'light' || localStorage.getItem('theme') === 'light';
            if (isLight) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        }
        document.addEventListener('livewire:navigated', applyTheme);
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $title ?? 'Dashboard Admin' }} - {{ config('app.name', 'RentSpace') }}</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#09090b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RENT ADMIN">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        
        /* Prevent Flash of Light Mode & Transitions */
        .dark { color-scheme: dark; }
        :root { color-scheme: light; }
        
        /* Disable transition during page load/navigate to stop blinking */
        .no-transitions * {
            transition: none !important;
        }

        html, body {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        /* Allow selection in inputs */
        input, textarea {
            user-select: text !important;
            -webkit-user-select: text !important;
        }

        /* Hide scrollbar but keep scroll functionality */
        ::-webkit-scrollbar {
            display: none;
        }
        html, body {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
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

        // Force disable zooming safely without re-declaration errors
        (function() {
            if (window._touchZoomPrevented) return;
            window._touchZoomPrevented = true;

            document.addEventListener('gesturestart', function(e) {
                e.preventDefault();
            });
            
            document.addEventListener('touchstart', function(event) {
                if (event.touches.length > 1) {
                    event.preventDefault();
                }
            }, { passive: false });

            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(event) {
                let now = (new Date()).getTime();
                if (now - lastTouchEnd <= 300) {
                    event.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
        })();
    </script>
</head>

<body class="bg-background min-h-screen text-foreground antialiased font-sans flex flex-col">

    <livewire:admin.admin-navbar />
    <livewire:admin.command-palette />

    <!-- Center Floating Elegant Apple-Style Spinner (DailyPhone style PageLoader) -->
    <div id="admin-global-loader"
         class="fixed inset-0 z-[9999] pointer-events-none flex items-center justify-center transition-opacity duration-200 opacity-0"
         style="display: none;">
        <!-- Very subtle page dim during active load ala DailyPhone -->
        <div class="absolute inset-0 bg-black/5 dark:bg-black/20 backdrop-blur-[2px]"></div>

        <!-- Centered loading pill -->
        <div class="relative flex items-center gap-2.5 px-4 py-2 rounded-full bg-card/90 dark:bg-[#1c1c1e]/90 backdrop-blur-2xl border border-border/70 shadow-2xl text-foreground font-medium text-xs tracking-tight pointer-events-auto">
            <svg class="animate-spin h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-semibold text-foreground">Loading…</span>
        </div>
    </div>

    <!-- Livewire Request / Navigate Global Loading Listener (Instant Feedback ala DailyPhone) -->
    <script>
        (function() {
            let loader = document.getElementById('admin-global-loader');
            let activeRequests = 0;
            let showTimestamp = 0;

            function showLoader() {
                activeRequests++;
                if (!loader) loader = document.getElementById('admin-global-loader');
                if (!loader) return;
                
                showTimestamp = Date.now();
                loader.style.display = 'flex';
                requestAnimationFrame(() => {
                    loader.classList.remove('opacity-0');
                    loader.classList.add('opacity-100');
                });
            }

            function hideLoader() {
                activeRequests = Math.max(0, activeRequests - 1);
                if (activeRequests > 0) return;

                if (!loader) loader = document.getElementById('admin-global-loader');
                if (!loader) return;

                const elapsed = Date.now() - showTimestamp;
                const minDisplayTime = 250;
                const delay = elapsed < minDisplayTime ? (minDisplayTime - elapsed) : 0;

                setTimeout(() => {
                    if (activeRequests === 0 && loader) {
                        loader.classList.remove('opacity-100');
                        loader.classList.add('opacity-0');
                        setTimeout(() => {
                            if (activeRequests === 0 && loader.classList.contains('opacity-0')) {
                                loader.style.display = 'none';
                            }
                        }, 200);
                    }
                }, delay);
            }

            // Hook langsung saat link wire:navigate diklik pengguna
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]');
                if (link && link.getAttribute('href') && !link.getAttribute('href').startsWith('#') && !link.getAttribute('target')) {
                    showLoader();
                }
            }, true);

            // Hook ke event Livewire SPA navigation
            document.addEventListener('livewire:navigating', showLoader);
            document.addEventListener('livewire:navigated', () => {
                activeRequests = 0;
                hideLoader();
            });

            // Hook ke event Livewire commit (klik tombol / action / filter)
            document.addEventListener('livewire:init', () => {
                if (window.Livewire && Livewire.hook) {
                    Livewire.hook('commit', ({ succeed, fail }) => {
                        showLoader();
                        succeed(() => hideLoader());
                        fail(() => hideLoader());
                    });
                }
            });
        })();
    </script>

    <!-- Main Content with Dynamic Island / Notch safe area padding on mobile -->
    <main class="flex-1 w-full max-w-7xl mx-auto pb-24 sm:pb-28 px-4 sm:px-6 lg:px-8"
          style="padding-top: max(1.25rem, calc(env(safe-area-inset-top, 0px) + 0.75rem));">
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

    <script>
        // Register Service Worker for PWA silently
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>

    <x-pwa-install-prompt />
    @livewireScripts
</body>
</html>