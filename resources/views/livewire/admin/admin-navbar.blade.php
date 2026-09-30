<div x-data="{ 
    adminMenuOpen: false,
    darkMode: document.documentElement.classList.contains('dark'),
    init() {
        this.darkMode = document.documentElement.classList.contains('dark');
        window.addEventListener('theme-changed', (e) => {
            this.darkMode = e.detail.theme === 'dark';
        });
    },
    toggleTheme() {
        this.darkMode = !this.darkMode;
        const newTheme = this.darkMode ? 'dark' : 'light';
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            localStorage.theme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            localStorage.theme = 'light';
        }
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: newTheme } }));
    }
}" 
class="fixed bottom-2 sm:bottom-4 left-2 right-2 sm:left-6 sm:right-6 z-50 pointer-events-none transition-all duration-300"
style="padding-bottom: max(0.25rem, env(safe-area-inset-bottom, 0px));">

    <!-- Mobile Drawer / Popover More Menu (Transparent Blur Glass) -->
    <div x-show="adminMenuOpen" 
         @click.away="adminMenuOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95" 
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95" 
         x-cloak
         class="pointer-events-auto mx-auto max-w-sm w-full mb-2 bg-background/60 dark:bg-[#161617]/60 backdrop-blur-3xl saturate-150 border border-border/60 shadow-2xl rounded-3xl overflow-hidden p-3 z-50">
        
        <!-- Drag indicator bar -->
        <div class="flex justify-center pt-1 pb-2">
            <div class="w-8 h-1 rounded-full bg-foreground/20"></div>
        </div>

        <!-- User & Theme bar -->
        <div class="flex items-center justify-between px-3 py-2 border-b border-border/40">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="h-7 w-7 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
                    <span class="text-xs font-bold text-primary">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-foreground truncate">{{ auth()->user()->name ?? 'Administrator' }}</p>
                    <p class="text-[10px] text-muted-foreground capitalize">{{ auth()->user()->role ?? 'Admin' }}</p>
                </div>
            </div>
            <button @click="toggleTheme()"
                type="button"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-foreground/5 hover:bg-foreground/10 text-foreground transition-all">
                <template x-if="!darkMode">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                        </svg>
                        <span>Gelap</span>
                    </div>
                </template>
                <template x-if="darkMode">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4" /><path d="M12 2v2" /><path d="M12 20v2" /><path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" /><path d="M2 12h2" /><path d="M20 12h2" /><path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
                        </svg>
                        <span>Terang</span>
                    </div>
                </template>
            </button>
        </div>

        <!-- Menu List (Database & System) -->
        <div class="py-2 space-y-1 max-h-[300px] overflow-y-auto">
            <div class="text-[9px] font-black uppercase text-muted-foreground/60 px-3 py-1 tracking-wider">Database & Manajemen</div>
            <a href="{{ route('admin.units') }}" wire:navigate @click="adminMenuOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.units') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                </div>
                <span class="flex-1">Data Unit</span>
                <span class="text-xs text-muted-foreground/40">›</span>
            </a>

            <a href="{{ route('admin.promo') }}" wire:navigate @click="adminMenuOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.promo') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                </div>
                <span class="flex-1">Promo & Diskon</span>
                <span class="text-xs text-muted-foreground/40">›</span>
            </a>

            <a href="{{ route('admin.instagram-story') }}" wire:navigate @click="adminMenuOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.instagram-story') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.instagram-story') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                </div>
                <span class="flex-1">Instagram Story</span>
                <span class="text-xs text-muted-foreground/40">›</span>
            </a>

            @if(auth()->user()->role !== 'staff')
            <a href="{{ route('admin.customers') }}" wire:navigate @click="adminMenuOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.customers') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.customers') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="flex-1">Pelanggan</span>
                <span class="text-xs text-muted-foreground/40">›</span>
            </a>
            @endif

            <!-- Activity / Staff Logs in Mobile Drawer -->
            <a href="{{ route('admin.staff-logs') }}" wire:navigate @click="adminMenuOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.staff-logs') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.staff-logs') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>
                </div>
                <span class="flex-1">Activity Log</span>
                <span class="text-xs text-muted-foreground/40">›</span>
            </a>

            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.affiliate') }}" wire:navigate @click="adminMenuOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.affiliate') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                    <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.affiliate') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
                    </div>
                    <span class="flex-1">Affiliate</span>
                    <span class="text-xs text-muted-foreground/40">›</span>
                </a>

                <a href="{{ route('admin.settings') }}" wire:navigate @click="adminMenuOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary' : 'hover:bg-foreground/5 text-foreground' }}">
                    <div class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 {{ request()->routeIs('admin.settings') ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <span class="flex-1">Pengaturan</span>
                    <span class="text-xs text-muted-foreground/40">›</span>
                </a>
            @endif
        </div>

        <!-- Footer link & Logout -->
        <div class="pt-2 border-t border-border/40 flex items-center justify-between gap-2">
            <a href="/" wire:navigate
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-foreground/5 hover:bg-foreground/10 text-foreground transition">
                <span>Web Publik</span>
                <span class="text-[10px]">↗</span>
            </a>
            <button wire:click="logout"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-destructive/10 hover:bg-destructive/15 text-destructive transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                <span>Keluar</span>
            </button>
        </div>
    </div>

    <!-- Floating Liquid Glass Bottom Dock (Apple / DailyPhone Style) -->
    <nav class="pointer-events-auto mx-auto max-w-fit px-2 sm:px-3 h-14 sm:h-[58px] rounded-full border border-border/60 bg-background/55 dark:bg-[#161617]/55 backdrop-blur-3xl saturate-180 shadow-2xl flex items-center gap-1 sm:gap-2 transition-all duration-300">
        
        <!-- Tab: Dashboard -->
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-full text-[11px] sm:text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary shadow-xs' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.dashboard') ? '2.2' : '1.8' }}" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
            </svg>
            <span class="tracking-tight text-[10px] sm:text-xs">Dashboard</span>
        </a>

        <!-- Tab: Monitoring -->
        <a href="{{ route('admin.monitoring') }}" wire:navigate
            class="flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-full text-[11px] sm:text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.monitoring') ? 'bg-primary/10 text-primary shadow-xs' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.monitoring') ? '2.2' : '1.8' }}" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.48 12H2"/>
            </svg>
            <span class="tracking-tight text-[10px] sm:text-xs">Monitoring</span>
        </a>

        <!-- Tab: Transaksi -->
        <a href="{{ route('admin.transactions') }}" wire:navigate
            class="flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-full text-[11px] sm:text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary shadow-xs' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.transactions') ? '2.2' : '1.8' }}" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
            </svg>
            <span class="tracking-tight text-[10px] sm:text-xs">Transaksi</span>
        </a>

        <!-- Tab: Activity (ala DailyPhone) -->
        <a href="{{ route('admin.staff-logs') }}" wire:navigate
            class="flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-full text-[11px] sm:text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.staff-logs') ? 'bg-primary/10 text-primary shadow-xs' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.staff-logs') ? '2.2' : '1.8' }}" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20v-6M6 20V10M18 20V4"/>
            </svg>
            <span class="tracking-tight text-[10px] sm:text-xs">Activity</span>
        </a>

        <!-- Desktop Direct Links for Database items -->
        <div class="hidden md:flex items-center gap-1 border-l border-border/40 pl-1.5 ml-0.5">
            <a href="{{ route('admin.units') }}" wire:navigate
                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 {{ request()->routeIs('admin.units') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                <span>Unit</span>
            </a>

            <a href="{{ route('admin.instagram-story') }}" wire:navigate
                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 {{ request()->routeIs('admin.instagram-story') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                <span>Story IG</span>
            </a>

            <a href="{{ route('admin.promo') }}" wire:navigate
                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 {{ request()->routeIs('admin.promo') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                <span>Promo</span>
            </a>

            @if(auth()->user()->role !== 'staff')
            <a href="{{ route('admin.customers') }}" wire:navigate
                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 {{ request()->routeIs('admin.customers') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Pelanggan</span>
            </a>
            @endif

            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.settings') }}" wire:navigate
                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Setting</span>
                </a>
            @endif
        </div>

        <!-- More Toggle (Active on mobile or extra menu) -->
        <button @click="adminMenuOpen = !adminMenuOpen"
            class="flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-full text-[11px] sm:text-xs font-semibold transition-all duration-200"
            :class="adminMenuOpen ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground'">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
            </svg>
            <span class="tracking-tight text-[10px] sm:text-xs">More</span>
        </button>
    </nav>
</div>