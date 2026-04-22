@php
    $id = $activeAnnouncement?->id ?? 0;
@endphp

<div x-data="{ 
    showBanner: true, 
    showPopup: false,
    init() {
        @if($activeAnnouncement && $activeAnnouncement->type === 'popup')
            const dismissed = sessionStorage.getItem('announcement_dismissed_{{ $id }}');
            if (!dismissed) {
                setTimeout(() => { this.showPopup = true; }, 1000);
            }
        @endif
    },
    dismissPopup() {
        this.showPopup = false;
        sessionStorage.setItem('announcement_dismissed_{{ $id }}', 'true');
    }
}">
    @if($activeAnnouncement && $placement === 'top' && ($activeAnnouncement->type === 'banner' || $activeAnnouncement->type === 'flash'))
        <div x-show="showBanner" x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-full" class="relative isolate flex items-center gap-x-4 overflow-hidden px-4 py-2 sm:px-6 sm:py-2.5 sm:before:flex-1
            {{ $activeAnnouncement->style === 'promo' ? 'bg-purple-600 text-white' : '' }}
            {{ $activeAnnouncement->style === 'info' ? 'bg-blue-600 text-white' : '' }}
            {{ $activeAnnouncement->style === 'warning' ? 'bg-amber-500 text-black' : '' }}
            {{ $activeAnnouncement->style === 'success' ? 'bg-emerald-600 text-white' : '' }}">

            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-1">
                <p class="text-xs sm:text-sm leading-6 text-center sm:text-left">
                    <strong
                        class="font-bold uppercase tracking-wider text-[9px] bg-white/20 px-1.5 py-0.5 rounded mr-2">{{ $activeAnnouncement->type === 'flash' ? 'Hot News' : 'Promo' }}</strong>
                    {{ $activeAnnouncement->message }}
                </p>
                @if($activeAnnouncement->link_url)
                    <a href="{{ $activeAnnouncement->link_url }}"
                        class="flex-none rounded-full bg-foreground/10 px-3 py-1 text-[11px] sm:text-sm font-semibold shadow-sm hover:bg-foreground/20 transition-colors">
                        {{ $activeAnnouncement->link_text ?: 'Cek' }} <span aria-hidden="true"
                            class="hidden sm:inline">&rarr;</span>
                    </a>
                @endif
            </div>
            <div class="flex flex-1 justify-end">
                <button @click="showBanner = false" type="button"
                    class="-m-3 p-3 focus-visible:outline-offset-[-4px] opacity-70 hover:opacity-100">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path
                            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if($activeAnnouncement && $placement === 'hero' && $activeAnnouncement->type === 'container')
        <!-- Container Mode -->
        <div x-show="showBanner"
            class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 sm:mt-8 animate-in slide-in-from-top duration-500">
            <div class="relative overflow-hidden rounded-xl sm:rounded-2xl px-4 py-4 sm:px-6 sm:py-5 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-6 border border-white/10
                {{ $activeAnnouncement->style === 'promo' ? 'bg-purple-600 text-white' : '' }}
                {{ $activeAnnouncement->style === 'info' ? 'bg-blue-600 text-white' : '' }}
                {{ $activeAnnouncement->style === 'warning' ? 'bg-amber-500 text-black border-amber-400/20' : '' }}
                {{ $activeAnnouncement->style === 'success' ? 'bg-emerald-600 text-white' : '' }}">

                <div class="text-center sm:text-left">
                    <h4 class="font-black uppercase tracking-tighter text-lg sm:text-xl leading-none mb-1">PENGUMUMAN
                        TERBARU</h4>
                    <p class="text-sm sm:text-base font-medium opacity-90">{{ $activeAnnouncement->message }}</p>
                </div>

                <div
                    class="flex items-center gap-4 sm:gap-6 w-full sm:w-auto justify-between sm:justify-end border-t border-white/10 sm:border-0 pt-4 sm:pt-0">
                    @if($activeAnnouncement->link_url)
                        <a href="{{ $activeAnnouncement->link_url }}"
                            class="flex-1 sm:flex-none text-center rounded-lg sm:rounded-xl bg-white/20 px-6 sm:px-8 py-2 sm:py-2.5 text-xs sm:text-sm font-bold shadow-sm hover:bg-white/30 backdrop-blur-sm transition-all active:scale-95">
                            {{ $activeAnnouncement->link_text ?: 'Dapatkan Sekarang' }}
                        </a>
                    @endif
                    <button @click="showBanner = false" class="p-1 opacity-60 hover:opacity-100 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="sm:w-6 sm:h-6">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($activeAnnouncement && $placement === 'top' && $activeAnnouncement->type === 'popup')
        <!-- Popup Modal -->
        <div x-show="showPopup" x-cloak class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div x-show="showPopup" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-zinc-950/60 backdrop-blur-md" @click="dismissPopup()"></div>

            <!-- content -->
            <div x-show="showPopup" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative w-full max-w-lg overflow-hidden rounded-[2rem] bg-card border border-border shadow-2xl">

                <!-- Hero Color bar -->
                <div class="h-1.5 sm:h-2 w-full
                    {{ $activeAnnouncement->style === 'promo' ? 'bg-purple-600' : '' }}
                    {{ $activeAnnouncement->style === 'info' ? 'bg-blue-600' : '' }}
                    {{ $activeAnnouncement->style === 'warning' ? 'bg-amber-500' : '' }}
                    {{ $activeAnnouncement->style === 'success' ? 'bg-emerald-600' : '' }}">
                </div>

                <div class="p-6 sm:p-10 text-center pt-8 sm:pt-12">
                    <div class="mb-4 inline-flex items-center justify-center rounded-full px-4 py-1 text-[9px] sm:text-[10px] font-black uppercase tracking-widest
                        {{ $activeAnnouncement->style === 'promo' ? 'bg-purple-500/10 text-purple-600' : '' }}
                        {{ $activeAnnouncement->style === 'info' ? 'bg-blue-500/10 text-blue-600' : '' }}
                        {{ $activeAnnouncement->style === 'warning' ? 'bg-amber-500/10 text-amber-600' : '' }}
                        {{ $activeAnnouncement->style === 'success' ? 'bg-emerald-500/10 text-emerald-600' : '' }}">
                        PROMO TERBARU
                    </div>

                    <h3 class="text-lg sm:text-3xl font-black tracking-tight text-foreground leading-tight mb-4 sm:mb-6">
                        {{ $activeAnnouncement->message }}
                    </h3>

                    <div class="mt-6 sm:mt-10 flex flex-col gap-2.5 sm:gap-3">
                        @if($activeAnnouncement->link_url)
                            <a href="{{ $activeAnnouncement->link_url }}" @click="dismissPopup()"
                                class="inline-flex h-11 sm:h-12 items-center justify-center rounded-xl bg-primary px-8 text-xs sm:text-sm font-bold text-primary-foreground shadow-lg hover:bg-primary/90 transition-all transition-transform active:scale-95">
                                {{ $activeAnnouncement->link_text ?: 'Cek Sekarang' }}
                            </a>
                        @endif
                        <button @click="dismissPopup()"
                            class="inline-flex h-11 sm:h-12 items-center justify-center rounded-xl border border-input bg-background px-8 text-xs sm:text-sm font-bold text-muted-foreground hover:bg-muted transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>

                <button @click="dismissPopup()"
                    class="absolute top-4 right-4 p-2 text-muted-foreground hover:text-foreground transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="sm:w-5 sm:h-5">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>