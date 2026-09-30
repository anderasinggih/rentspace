<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    <title>{{ $title ?? 'IPHONE RENT SPACE PURWOKERTO' }}</title>
    
    <!-- Open Graph / Link Preview Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $metaTitle ?? $title ?? 'IPHONE RENT SPACE PURWOKERTO' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Penyewaan iPhone Terpercaya di Purwokerto. Proses cepat, unit berkualitas.' }}">
    <meta property="og:image" content="{{ $metaImage ?? asset('logo.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ $metaTitle ?? $title ?? 'IPHONE RENT SPACE PURWOKERTO' }}">
    <meta property="twitter:description" content="{{ $metaDescription ?? 'Penyewaan iPhone Terpercaya di Purwokerto. Proses cepat, unit berkualitas.' }}">
    <meta property="twitter:image" content="{{ $metaImage ?? asset('logo.png') }}">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RENT SPACE">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <script>
        // Otomatis redirect ke halaman admin / login admin jika dibuka dari PWA (Standalone Mode)
        (function() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            if (isStandalone && window.location.pathname === '/') {
                @auth
                    window.location.replace('/admin');
                @else
                    window.location.replace('/login');
                @endauth
            }
        })();

        function applyTheme() {
            // Default adalah dark mode ala Apple HIG Developer
            if (localStorage.theme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
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
        })();

        // Advanced Haptic Engine (Native Feeling for Web)
        window.hapticEngine = {
            canVibrate: !!navigator.vibrate,
            hapticSwitch: null,

            init: function() {
                this.hapticSwitch = document.getElementById('ios-haptic-switch');
            },

            trigger: function(type = 'light') {
                try {
                    // 1. Native Vibration (Android)
                    if (this.canVibrate) {
                        navigator.vibrate(type === 'success' ? [10, 30, 10] : 15);
                    }

                    // 2. The Switch Trick (iOS Safari)
                    if (this.hapticSwitch) {
                        this.hapticSwitch.click();
                    }
                } catch (e) {}
            }
        };

        document.addEventListener('DOMContentLoaded', () => window.hapticEngine.init());
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-haptic]');
            if (target) {
                window.hapticEngine.trigger(target.dataset.haptic);
            }
        }, { passive: true });
    </script>
    <input type="checkbox" id="ios-haptic-switch" aria-hidden="true" style="position: absolute; opacity: 0; pointer-events: none; left: -9999px;">
</head>

<body class="bg-background text-foreground antialiased font-sans flex flex-col min-h-screen">
    <livewire:front.global-announcement placement="top" />

    <!-- Center Floating Elegant Apple-Style Spinner (DailyPhone style PageLoader) -->
    <div id="global-page-loader"
         class="fixed inset-0 z-[9999] pointer-events-none flex items-center justify-center transition-opacity duration-200 opacity-0"
         style="display: none;">
        <!-- Very subtle page dim during active load ala DailyPhone -->
        <div class="absolute inset-0 bg-black/10 dark:bg-black/30 backdrop-blur-[2px]"></div>

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
            let loader = document.getElementById('global-page-loader');
            let activeRequests = 0;
            let showTimestamp = 0;

            function showLoader() {
                activeRequests++;
                if (!loader) loader = document.getElementById('global-page-loader');
                if (!loader) return;
                
                showTimestamp = Date.now();
                loader.style.display = 'flex';
                // Trigger class transition seketika
                requestAnimationFrame(() => {
                    loader.classList.remove('opacity-0');
                    loader.classList.add('opacity-100');
                });
            }

            function hideLoader() {
                activeRequests = Math.max(0, activeRequests - 1);
                if (activeRequests > 0) return;

                if (!loader) loader = document.getElementById('global-page-loader');
                if (!loader) return;

                // Berikan jeda minimum 250ms agar animasi spinner Apple terlihat halus dan tidak berkedip (flicker)
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

    @Unless ($hideNavbar ?? false)
    <livewire:navbar />
    @endUnless
    <main class="flex-1 w-full flex flex-col">
        {{ $slot }}
    </main>

    @unless($hideFooter ?? false)
    <x-front.footer />
    @endunless

    @php 
        $osAppId = \App\Models\Setting::getVal('onesignal_app_id');
        $osSafariId = \App\Models\Setting::getVal('onesignal_safari_web_id');
    @endphp

    @if($osAppId)
    <!-- OneSignal Integration -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
        if (!window._oneSignalInitialized) {
            window._oneSignalInitialized = true;
            window.OneSignalDeferred = window.OneSignalDeferred || [];
            OneSignalDeferred.push(async function(OneSignal) {
                try {
                    await OneSignal.init({
                        appId: "{{ $osAppId }}",
                        @if($osSafariId) safari_web_id: "{{ $osSafariId }}", @endif
                        allowLocalhostAsSecureContext: true,
                    });

                    setTimeout(async () => {
                        const permission = OneSignal.Notifications.permission;
                        if (permission === 'default') {
                            await OneSignal.showSlidedownPrompt();
                        }

                        @auth
                            await OneSignal.login("{{ auth()->id() }}");
                            await OneSignal.User.addTag("role", "{{ auth()->user()->role }}");
                        @else
                            if (OneSignal.User.externalId) {
                                await OneSignal.logout();
                            }
                        @endauth
                    }, 2000);
                } catch (e) {
                    // Suppress duplicate init warnings
                }
            });
        }
    </script>
    @endif

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