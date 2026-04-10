<nav x-data="{ adminMenuOpen: false }" class="bg-background border-b border-border sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left side Navigation -->
            <div class="flex items-center gap-6">
                <!-- Logo -->
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-xl font-extrabold tracking-tight text-foreground flex items-center gap-2 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                    Admin<span class="text-primary/80">Space</span>
                </a>
                
                <!-- Desktop Links -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Analitik Dashboard
                    </a>
                    <a href="{{ route('admin.units') }}" wire:navigate class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Manajemen Unit
                    </a>
                    <a href="{{ route('admin.promo') }}" wire:navigate class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Promo & Diskon
                    </a>
                    <a href="{{ route('admin.transactions') }}" wire:navigate class="px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-muted hover:text-foreground {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary font-semibold' : 'text-muted-foreground' }}">
                        Mutasi Transaksi
                    </a>
                </div>
            </div>

            <!-- Right side context -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="/" wire:navigate class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-secondary text-secondary-foreground hover:bg-secondary/80 transition-colors">
                    Lihat Web Publik ↗
                </a>
                
                <div class="border-l border-border h-6 mx-2 hidden sm:block"></div>

                <div class="hidden sm:flex items-center gap-3">
                    <span class="text-sm font-medium text-foreground">{{ auth()->user()->name ?? 'Administrator' }}</span>
                    <button wire:click="logout" class="inline-flex items-center justify-center p-2 rounded-md text-muted-foreground hover:bg-destructive hover:text-destructive-foreground transition-colors" title="Logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    </button>
                </div>

                <!-- Mobile Hamburger -->
                <button @click="adminMenuOpen = !adminMenuOpen" class="md:hidden p-2 rounded-md hover:bg-muted text-foreground transition-colors focus:outline-none">
                    <svg x-show="!adminMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    <svg x-show="adminMenuOpen" x-cloak style="display: none;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="adminMenuOpen" 
         x-transition
         x-cloak style="display: none;"
         class="md:hidden border-t border-border bg-background px-4 py-4 space-y-2 pb-6">
        
        <div class="text-xs font-semibold uppercase text-muted-foreground mb-2">Menu Utama</div>
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Analitik Dashboard</a>
        <a href="{{ route('admin.units') }}" wire:navigate class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Manajemen Unit</a>
        <a href="{{ route('admin.promo') }}" wire:navigate class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Promo & Diskon</a>
        <a href="{{ route('admin.transactions') }}" wire:navigate class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted' }}">Mutasi Transaksi</a>
        
        <div class="h-px bg-border my-4"></div>
        
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium">{{ auth()->user()->name ?? 'Administrator' }}</span>
            <button wire:click="logout" class="text-sm text-destructive font-medium px-3 py-1.5 rounded-md hover:bg-destructive/10">Logout</button>
        </div>
        <a href="/" wire:navigate class="block text-center mt-2 px-3 py-2 rounded-md text-sm font-medium bg-secondary text-secondary-foreground">Ke Web Publik</a>
    </div>
</nav>
