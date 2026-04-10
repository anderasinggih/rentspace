<nav class="fixed top-4 left-4 right-4 z-50 mx-auto max-w-6xl rounded-2xl border border-border bg-background/70 backdrop-blur-lg shadow-sm transition-colors duration-300">
    <div class="px-6 flex h-14 items-center justify-between">
        <div class="flex items-center gap-8">
            <a href="/" class="flex items-center space-x-2">
                <span class="font-bold text-xl tracking-tight">Sewa<span class="text-primary/70">Phone</span></span>
            </a>
            <div class="hidden md:flex items-center gap-6 text-sm font-medium text-muted-foreground">
                <a href="{{ route('public.timeline') }}" wire:navigate class="transition-colors hover:text-foreground">Sewa iPhone</a>
                <a href="/" class="transition-colors hover:text-foreground">Tentang</a>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Dark mode toggle -->
            <button @click="darkMode = !darkMode" class="p-2 rounded-full hover:bg-muted text-muted-foreground hover:text-foreground focus:outline-none transition-colors border border-transparent hover:border-border">
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                <svg x-cloak x-show="darkMode" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </button>
            <a href="{{ route('admin.units') }}" wire:navigate class="inline-flex items-center justify-center rounded-full text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-muted hover:text-foreground h-9 px-5 py-2">
                Panel Admin
            </a>
        </div>
    </div>
</nav>
