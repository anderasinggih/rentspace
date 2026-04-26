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
}" class="bg-background/5 sticky backdrop-blur-md top-0 z-[100] border-b border-white/10 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 text-foreground">
            <!-- Left side Navigation -->
            <div class="flex items-center gap-6">
                <!-- Logo -->
                <a href="{{ route('admin.dashboard') }}" wire:navigate
                    class="text-xl font-extrabold tracking-tight text-foreground flex items-center gap-2 mr-2">
                    RENT<span class="text-primary/80">SPACE</span>
                </a>

                <!-- Desktop Links -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate
                        class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('admin.monitoring') }}" wire:navigate
                        class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.monitoring') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Monitoring
                    </a>

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
                            <div
                                class="px-3 py-1 text-[9px] font-black uppercase text-muted-foreground/50 tracking-widest">
                                Inventory</div>
                            <a href="{{ route('admin.units') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.units') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Unit
                            </a>
                            <a href="{{ route('admin.promo') }}" wire:navigate
                                class="block px-4 py-2 text-sm transition-colors hover:bg-muted {{ request()->routeIs('admin.promo') ? 'text-primary font-bold' : 'text-muted-foreground' }}">
                                Promo & Diskon
                            </a>

                            <div class="h-px bg-border/50 my-1 mx-2"></div>
                            <div
                                class="px-3 py-1 text-[9px] font-black uppercase text-muted-foreground/50 tracking-widest">
                                Resources</div>

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
                </a>                <!-- Quick Scan Button -->
                <a href="{{ route('admin.scan') }}" wire:navigate
                    class="p-2 flex items-center justify-center rounded-md hover:bg-primary/10 text-primary transition-all hover:scale-110 active:scale-95 focus:outline-none {{ request()->routeIs('admin.scan') ? 'bg-primary/20 bg-primary shadow-sm' : '' }}"
                    title="Quick Scan QR">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                        <rect width="7" height="7" x="7" y="7" rx="1"/>
                        <path d="M10 17h.01"/><path d="M17 10h.01"/><path d="M17 17h.01"/>
                    </svg>
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
        class="md:hidden absolute top-[115%] left-0 right-0 p-3 bg-background/5 backdrop-blur-md border border-white/10 border-t-white/20 shadow-2xl rounded-3xl mx-4 overflow-hidden flex flex-col gap-1 z-[110]">
        
        <!-- Shine Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/5 via-transparent to-transparent pointer-events-none"></div>

        <div class="text-[10px] font-bold uppercase text-muted-foreground px-4 py-2 mt-2 tracking-widest opacity-60">Utama</div>
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Dashboard</a>
        <a href="{{ route('admin.monitoring') }}" wire:navigate
            class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.monitoring') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Monitoring</a>
        <div class="h-px bg-white/10 my-1 mx-4"></div>
        <a href="{{ route('admin.transactions') }}" wire:navigate
            class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.transactions') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Transaksi</a>

        <div class="h-px bg-white/10 my-1 mx-4"></div>
        <div class="text-[10px] font-bold uppercase text-muted-foreground px-4 py-2 tracking-widest opacity-60">Database & Sistem</div>

        <a href="{{ route('admin.units') }}" wire:navigate
            class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.units') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Unit</a>
        <a href="{{ route('admin.promo') }}" wire:navigate
            class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.promo') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Promo & Diskon</a>
        <a href="{{ route('admin.customers') }}" wire:navigate
            class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.customers') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Pelanggan</a>
        
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.affiliate') }}" wire:navigate
                class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.affiliate') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Affiliate</a>
            <a href="{{ route('admin.settings') }}" wire:navigate
                class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.settings') ? 'bg-black/5 dark:bg-white/10 text-primary font-bold' : 'text-foreground hover:bg-black/5 dark:hover:bg-white/5' }}">Pengaturan</a>
        @endif

        <div class="h-px bg-white/10 my-2 mx-4"></div>

        <div class="flex items-center justify-between px-4 py-2">
            <span class="text-xs font-semibold opacity-70">{{ auth()->user()->name ?? 'Administrator' }}</span>
            <button wire:click="logout"
                class="text-xs text-destructive font-bold px-3 py-1.5 rounded-lg hover:bg-destructive/10 transition-colors">Logout</button>
        </div>
        <a href="/" wire:navigate
            class="flex items-center justify-center rounded-2xl bg-foreground text-background text-sm font-semibold px-4 py-3.5 hover:bg-foreground/90 active:scale-95 transition-all mt-1">
            Ke Web Publik
        </a>
    </div>
</nav>