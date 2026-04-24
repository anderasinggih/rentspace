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
                <div class="hidden md:flex items-center space-x-1">
                    <!-- Dropdown Dashboard (Overview & Monitoring) -->
                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.monitoring') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                            Dashboard
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" class="transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute left-0 mt-1 w-48 rounded-xl bg-background border border-border shadow-xl py-1.5 z-[110]"
                            x-cloak>
                            <a href="{{ route('admin.dashboard') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.dashboard') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Statistik (Utama)
                            </a>
                            <a href="{{ route('admin.monitoring') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.monitoring') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Monitoring (Live)
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('admin.transactions') }}" wire:navigate
                        class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Transaksi
                    </a>

                    <!-- Dropdown Database (Unit, Promo, Pelanggan, Affiliate, Settings) -->
                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.units') || request()->routeIs('admin.promo') || request()->routeIs('admin.customers') || request()->routeIs('admin.affiliate') || request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                            Database
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" class="transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute left-0 mt-1 w-48 rounded-xl bg-background border border-border shadow-xl py-1.5 z-[110]"
                            x-cloak>
                            <div class="px-3 py-1 text-[9px] font-black uppercase text-muted-foreground/50 tracking-widest">Inventory</div>
                            <a href="{{ route('admin.units') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.units') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Unit
                            </a>
                            <a href="{{ route('admin.promo') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.promo') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Promo & Diskon
                            </a>
                            
                            <div class="h-px bg-border/50 my-1 mx-2"></div>
                            <div class="px-3 py-1 text-[9px] font-black uppercase text-muted-foreground/50 tracking-widest">Resources</div>
                            
                            <a href="{{ route('admin.customers') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.customers') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Pelanggan
                            </a>
                            @if(auth()->user()->role === 'admin')
                                <a href="{{ route('admin.affiliate') }}" wire:navigate
                                    class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.affiliate') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                    Affiliate
                                </a>
                                <div class="h-px bg-border/50 my-1 mx-2"></div>
                                <a href="{{ route('admin.settings') }}" wire:navigate
                                    class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.settings') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                    Pengaturan
                                </a>
                            @endif
                        </div>
                    </div>
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

        <div class="text-[10px] font-bold uppercase text-muted-foreground px-3 mb-1 tracking-widest">Dashboard</div>
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary font-semibold' : 'text-foreground hover:bg-muted' }}">Statistik Utama</a>
        <a href="{{ route('admin.monitoring') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.monitoring') ? 'bg-primary/10 text-primary font-semibold' : 'text-foreground hover:bg-muted' }}">Monitoring Live</a>
        <div class="h-px bg-border/50 my-2 mx-3"></div>
        <a href="{{ route('admin.transactions') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary font-semibold' : 'text-foreground hover:bg-muted' }}">Transaksi</a>

        <div class="h-px bg-border/50 my-2 mx-3"></div>
        <div class="text-[10px] font-bold uppercase text-muted-foreground px-3 mb-1 tracking-widest">Database & Sistem</div>
        
        <a href="{{ route('admin.units') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Unit (Inventaris)</a>
        <a href="{{ route('admin.promo') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Promo & Diskon</a>
        
        <div class="h-px bg-border/40 my-1 mx-3"></div>

        <a href="{{ route('admin.customers') }}" wire:navigate
            class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.customers') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Pelanggan</a>
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.affiliate') }}" wire:navigate
                class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.affiliate') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Affiliate</a>
            <a href="{{ route('admin.settings') }}" wire:navigate
                class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Pengaturan</a>
        @endif

        <div class="h-px bg-border/50 my-4 mx-3"></div>

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