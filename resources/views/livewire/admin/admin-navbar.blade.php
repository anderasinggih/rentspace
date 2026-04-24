<nav x-data="{ 
    adminMenuOpen: false,
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
}" class="bg-background/10 sticky backdrop-blur-sm top-0 z-[100] shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left side Navigation -->
            <div class="flex items-center gap-6">
                <!-- Logo -->
                <a href="{{ route('admin.dashboard') }}" wire:navigate
                    class="text-xl font-extrabold tracking-tight text-foreground flex items-center gap-2 mr-2">
                    RENT<span class="text-primary/80">SPACE</span>
                </a>

                <!-- Desktop Links -->
                <div class="hidden md:flex items-center space-x-1 ml-4 gap-1">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate title="Dashboard"
                        class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </a>
                    <a href="{{ route('admin.units') }}" wire:navigate title="Unit & Stok"
                        class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                    </a>
                    <a href="{{ route('admin.promo') }}" wire:navigate title="Manajemen Promo"
                        class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9V5.25A2.25 2.25 0 0 1 4.25 3H20a2 2 0 0 1 2 2v4a2 2 0 0 0-2 2 2 2 0 0 0 2 2v4a2 2 0 0 1-2 2H4.25A2.25 2.25 0 0 1 2 18.75V15a2 2 0 0 0 2-2 2 2 0 0 0-2-2Z"/><path d="m15 9-6 6"/><path d="M9 9h.01"/><path d="M15 15h.01"/></svg>
                    </a>
                    <a href="{{ route('admin.transactions') }}" wire:navigate title="Daftar Transaksi"
                        class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5V18.5"/><path d="M12 5.5V6.5"/></svg>
                    </a>
                    <a href="{{ route('admin.monitoring') }}" wire:navigate title="Monitoring & Timeline"
                        class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.monitoring') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </a>
                    <a href="{{ route('admin.customers') }}" wire:navigate title="Data Pelanggan"
                        class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.customers') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </a>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.affiliate') }}" wire:navigate title="Affiliate Manager"
                            class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.affiliate') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                        </a>
                        <a href="{{ route('admin.settings') }}" wire:navigate title="Pengaturan Sistem"
                            class="p-2 rounded-xl transition-all duration-300 hover:bg-muted group {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1-1 1.73l-.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Right side context -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="/" wire:navigate
                    class="hidden xl:inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-secondary text-secondary-foreground hover:bg-secondary/80 transition-colors">
                    Lihat Web Publik ↗
                </a>

                <!-- Dark Mode Toggle Admin -->
                <button @click="toggleTheme()"
                    class="p-2 items-center justify-center rounded-md hover:bg-muted text-muted-foreground transition-colors focus:outline-none">
                    <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                    </svg>
                    <svg x-cloak x-show="darkMode" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="18"
                        height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
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

                <div class="border-l border-border h-6 mx-2 hidden sm:block"></div>

                <div class="hidden sm:flex items-center gap-3">
                    <span class="text-sm font-medium text-foreground">{{ auth()->user()->name ?? 'Administrator'
                        }}</span>
                    <button wire:click="logout"
                        class="inline-flex items-center justify-center p-2 rounded-md text-muted-foreground hover:bg-destructive hover:text-destructive-foreground transition-colors"
                        title="Logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" x2="9" y1="12" y2="12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Hamburger -->
                <button @click="adminMenuOpen = !adminMenuOpen"
                    class="md:hidden p-2 rounded-md hover:bg-muted text-foreground transition-colors focus:outline-none">
                    <svg x-show="!adminMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <line x1="4" x2="20" y1="12" y2="12" />
                        <line x1="4" x2="20" y1="6" y2="6" />
                        <line x1="4" x2="20" y1="18" y2="18" />
                    </svg>
                    <svg x-show="adminMenuOpen" x-cloak style="display: none;" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="adminMenuOpen" x-transition x-cloak style="display: none;"
        class="md:hidden border-t border-border bg-background px-4 py-4 space-y-2 pb-6">

        <div class="text-xs font-semibold uppercase text-muted-foreground mb-2">Menu Utama</div>
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Dashboard</a>
        <a href="{{ route('admin.units') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Unit</a>
        <a href="{{ route('admin.promo') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Promo</a>
        <a href="{{ route('admin.transactions') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Transaksi</a>
        <a href="{{ route('admin.monitoring') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.monitoring') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Monitoring</a>
        <a href="{{ route('admin.customers') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.customers') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Pelanggan</a>
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.affiliate') }}" wire:navigate
                class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.affiliate') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Affiliate</a>
            <a href="{{ route('admin.settings') }}" wire:navigate
                class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Pengaturan</a>
        @endif

        <div class="h-px bg-border my-4"></div>

        <div class="flex items-center justify-between">
            <span class="text-sm font-medium">{{ auth()->user()->name ?? 'Administrator' }}</span>
            <button wire:click="logout"
                class="text-sm text-destructive font-medium px-3 py-1.5 rounded-md hover:bg-destructive/10">Logout</button>
        </div>
        <a href="/" wire:navigate
            class="block text-center mt-2 px-3 py-2 rounded-md text-sm font-medium bg-secondary text-secondary-foreground">Ke
            Web Publik</a>
    </div>
</nav>