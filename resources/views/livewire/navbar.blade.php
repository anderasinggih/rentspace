<nav x-data="{ mobileMenuOpen: false }" class="fixed top-4 inset-x-4 max-w-7xl mx-auto z-50 bg-background/70 backdrop-blur-md border border-border/50 rounded-2xl shadow-sm px-6 py-3 transition-colors">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="/" wire:navigate class="flex items-center gap-2 mr-4">
                <span class="font-bold text-xl tracking-tight">Sewa<span class="text-primary/70">Phone</span></span>
            </a>
            
            <!-- Desktop Menu (Shadcn NavigationMenu style) -->
            <div class="hidden md:flex items-center space-x-1">
                <a href="/" class="group inline-flex h-9 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-muted hover:text-foreground focus:bg-muted focus:text-foreground focus:outline-none disabled:pointer-events-none disabled:opacity-50 data-[active]:bg-muted/50 data-[state=open]:bg-muted/50 {{ request()->is('/') ? 'bg-muted text-foreground' : 'bg-transparent text-muted-foreground' }}">Beranda</a>
                <a href="{{ route('public.timeline') }}" wire:navigate class="group inline-flex h-9 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-muted hover:text-foreground focus:bg-muted focus:text-foreground focus:outline-none disabled:pointer-events-none disabled:opacity-50 {{ request()->routeIs('public.timeline') ? 'bg-muted text-foreground' : 'bg-transparent text-muted-foreground' }}">Jadwal & Ketersediaan</a>
                <a href="{{ route('public.booking') }}" wire:navigate class="group inline-flex h-9 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-muted hover:text-foreground focus:bg-muted focus:text-foreground focus:outline-none disabled:pointer-events-none disabled:opacity-50 {{ request()->routeIs('public.booking') ? 'bg-muted text-foreground' : 'bg-transparent text-muted-foreground' }}">Form Booking</a>
            </div>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-4">
            <!-- Dark mode toggle -->
            <button @click="darkMode = !darkMode" class="p-2 mr-1 rounded-full hover:bg-muted text-muted-foreground hover:text-foreground focus:outline-none transition-colors border border-transparent hover:border-border">
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                <svg x-cloak x-show="darkMode" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </button>

            <!-- Login / Panel Admin -->
            <a href="{{ auth()->check() ? route('admin.units') : route('login') }}" wire:navigate class="hidden sm:inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring border border-input bg-background shadow-sm hover:bg-muted hover:text-foreground h-9 px-4 py-2">
                {{ auth()->check() ? 'Dashboard Admin' : 'Admin Login' }}
            </a>

            <!-- Mobile Hamburger -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-md hover:bg-muted text-foreground transition-colors focus:outline-none">
                <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                <svg x-show="mobileMenuOpen" x-cloak style="display: none;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu Dropdown -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak style="display: none;"
         class="md:hidden absolute top-full left-0 right-0 mt-3 p-4 bg-background border border-border/50 rounded-2xl shadow-lg flex flex-col gap-2">
        
        <a href="/" class="px-4 py-3 rounded-lg text-sm font-medium {{ request()->is('/') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">Beranda</a>
        <a href="{{ route('public.timeline') }}" wire:navigate class="px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('public.timeline') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">Jadwal & Ketersediaan</a>
        <a href="{{ route('public.booking') }}" wire:navigate class="px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('public.booking') ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}">Form Booking</a>
        <div class="h-px bg-border my-2"></div>
        <a href="{{ auth()->check() ? route('admin.units') : route('login') }}" wire:navigate class="inline-flex w-full items-center justify-center rounded-md text-sm font-medium border border-input bg-background shadow-sm hover:bg-muted hover:text-foreground h-10 px-4 py-2">
            {{ auth()->check() ? 'Dashboard Admin' : 'Admin Login' }}
        </a>
    </div>
</nav>
