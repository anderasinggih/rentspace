<div x-data="{ 
    publicMenuOpen: false,
    showNavbar: true,
    lastScrollY: window.scrollY,
    darkMode: localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggleTheme() {
        this.darkMode = !this.darkMode;
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
        }
    }
}" 
x-init="window.addEventListener('scroll', () => { 
    showNavbar = window.scrollY < lastScrollY || window.scrollY < 50;
    lastScrollY = window.scrollY;
})"
class="sticky top-6 z-50 mx-auto px-4 w-full max-w-6xl mb-12 transition-all duration-700 ease-in-out"
:class="showNavbar ? 'translate-y-0 opacity-100 blur-none' : '-translate-y-32 opacity-0 blur-sm pointer-events-none'"
>
    <nav
        class="flex items-center justify-between w-full h-14 border border-white/10 border-t-white/30 border-l-white/20 bg-background/10 backdrop-blur-[4px] backdrop-saturate-[150%] shadow-xl shadow-black/5 rounded-full px-4 transition-all overflow-hidden">
        <!-- Left Side: Logo & Links -->
        <div class="flex items-center">
            <!-- Logo Box -->
            <a href="/" wire:navigate
                class="font-extrabold tracking-tight text-foreground flex items-center mr-6 shrink-0 transition-transform hover:scale-105">
                {{ config('app.name', 'RENT SPACE') }}
            </a>

            <!-- Desktop Navigation Links -->
            <div class="hidden md:flex items-center gap-6">
                <a href="/" wire:navigate
                    class="text-sm font-medium transition-colors {{ request()->is('/') ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground' }}">
                    Beranda
                </a>
                <a href="{{ route('public.timeline') }}" wire:navigate
                    class="text-sm font-medium transition-colors {{ request()->routeIs('public.timeline') ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground' }}">
                    Jadwal
                </a>
                <a href="{{ route('public.about') }}" wire:navigate
                    class="text-sm font-medium transition-colors {{ request()->routeIs('public.about') ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground' }}">
                    Tentang & FAQ
                </a>
                <a href="{{ route('affiliate.login') }}" wire:navigate
                    class="text-sm font-medium transition-colors {{ request()->routeIs('affiliate.*') ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground' }}">
                    Affiliate
                </a>
                @php
                    $navCustomer = session('customer_session');
                    $navIsLoggedIn = $navCustomer && isset($navCustomer['expires_at']) && now()->timestamp < $navCustomer['expires_at'];
                @endphp
                @if($navIsLoggedIn)
                    <a href="{{ route('public.check-order') }}" wire:navigate
                        class="text-sm font-medium transition-colors {{ request()->routeIs('public.check-order') ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground' }}">
                        Cek Pesanan
                    </a>
                    <a href="{{ route('customer.logout') }}" wire:navigate wire:confirm="Apakah Anda yakin ingin keluar?"
                        class="text-sm font-medium text-red-500 hover:text-red-400 transition-colors">
                        Keluar
                    </a>
                @else
                    <a href="{{ route('customer.login') }}" wire:navigate
                        class="text-sm font-medium transition-colors {{ request()->routeIs('customer.login') ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground' }}">
                        Masuk
                    </a>
                @endif
            </div>
        </div>

        <!-- Right Side: Utils & CTA -->
        <div class="flex items-center gap-1 sm:gap-3">
            <!-- Dark Mode Toggle -->
            <button @click="toggleTheme()"
                class="flex p-2 items-center justify-center rounded-full hover:bg-muted text-muted-foreground transition-colors focus:outline-none">
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-moon">
                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                </svg>
                <svg x-cloak x-show="darkMode" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2" />
                    <path d="M12 20v2" />
                    <path d="m4.93 4.93 1.41 1.41" />
                    <path d="m17.66 17.66 1.41 1.41" />
                    <path d="M2 12h2" />
                    <path d="M20 12h2" />
                    <path d="m6.34 17.66-1.41 1.41" />
                    <path d="m19.07 4.93-1.41 1.41" />
                </svg>
            </button>

            <!-- CTA Button -->
            <a href="{{ route('public.booking') }}" wire:navigate
                class="hidden sm:inline-flex items-center justify-center rounded-full bg-foreground text-background text-xs font-semibold px-5 py-2 hover:bg-foreground/90 transition-all shadow-sm shrink-0">
                Sewa Sekarang
            </a>

            <!-- Mobile Hamburger -->
            <button @click="publicMenuOpen = !publicMenuOpen"
                class="md:hidden p-2 rounded-full hover:bg-muted text-foreground transition-colors focus:outline-none">
                <svg x-show="!publicMenuOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <line x1="4" x2="20" y1="12" y2="12" />
                    <line x1="4" x2="20" y1="6" y2="6" />
                    <line x1="4" x2="20" y1="18" y2="18" />
                </svg>
                <svg x-show="publicMenuOpen" x-cloak style="display: none;" xmlns="http://www.w3.org/2000/svg"
                    width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
    </nav>

    <!-- Mobile Dropdown -->
    <div x-show="publicMenuOpen" x-transition x-cloak style="display: none;"
        class="md:hidden absolute top-[110%] left-0 right-0 p-4 bg-background border border-border shadow-lg rounded-2xl flex flex-col gap-2 mx-1">
        <a href="/" wire:navigate
            class="px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->is('/') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">Beranda</a>
        <a href="{{ route('public.timeline') }}" wire:navigate
            class="px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.timeline') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">Jadwal</a>
        <a href="{{ route('public.about') }}" wire:navigate
            class="px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.about') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
            Tentang & FAQ</a>
        <a href="{{ route('affiliate.login') }}" wire:navigate
            class="px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('affiliate.*') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
            Affiliate Center</a>
        @php
            $mobileCustomer = session('customer_session');
            $mobileIsLoggedIn = $mobileCustomer && isset($mobileCustomer['expires_at']) && now()->timestamp < $mobileCustomer['expires_at'];
        @endphp
        @if($mobileIsLoggedIn)
            <a href="{{ route('public.check-order') }}" wire:navigate
                class="px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.check-order') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                Cek Pesanan
            </a>
            <a href="{{ route('customer.logout') }}" wire:navigate wire:confirm="Apakah Anda yakin ingin keluar?"
                class="px-4 py-2.5 rounded-lg text-sm font-medium text-red-500 hover:bg-red-500/10">
                Keluar
            </a>
        @else
            <a href="{{ route('customer.login') }}" wire:navigate
                class="px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('customer.login') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">
                Masuk
            </a>
        @endif
        <div class="h-px bg-border my-1"></div>
        <a href="{{ route('public.booking') }}" wire:navigate
            class="flex items-center justify-center rounded-xl bg-foreground text-background text-sm font-semibold px-4 py-3 hover:bg-foreground/90 transition-all mt-2">
            Sewa Sekarang
        </a>
    </div>
</div>