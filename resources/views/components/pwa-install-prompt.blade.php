<div x-data="{ 
    showPrompt: false, 
    isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
    isStandalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
    deferredPrompt: null,

    init() {
        // Jangan munculkan jika sudah terinstall atau baru saja di-close
        const dismissed = localStorage.getItem('pwa-prompt-dismissed');
        const now = new Date().getTime();
        
        if (this.isStandalone || (dismissed && (now - dismissed < 86400000 * 3))) {
            return;
        }

        // Handler untuk Android (Chrome)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            setTimeout(() => { this.showPrompt = true; }, 3000);
        });

        // Khusus iOS (Safari tidak support beforeinstallprompt)
        if (this.isIOS && !this.isStandalone) {
            setTimeout(() => { this.showPrompt = true; }, 4000);
        }
    },

    dismiss() {
        this.showPrompt = false;
        localStorage.setItem('pwa-prompt-dismissed', new Date().getTime());
    },

    async install() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                this.showPrompt = false;
            }
            this.deferredPrompt = null;
        }
    }
}" 
x-show="showPrompt" 
x-transition:enter="transition ease-out duration-500"
x-transition:enter-start="opacity-0 translate-y-20"
x-transition:enter-end="opacity-100 translate-y-0"
x-transition:leave="transition ease-in duration-300"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 translate-y-20"
class="fixed bottom-6 left-4 right-4 z-[100] md:left-auto md:right-6 md:w-96"
style="display: none;">

    <div class="relative overflow-hidden rounded-3xl border border-white/20 bg-white/10 dark:bg-black/40 backdrop-blur-2xl p-5 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
        <!-- Shine Effect -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent pointer-events-none"></div>
        
        <div class="relative z-10 flex items-start gap-4">
            <!-- App Icon -->
            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-white/20 shadow-lg">
                <img src="{{ asset('logo.png') }}" class="h-full w-full object-cover" alt="App Icon">
            </div>

            <div class="flex-1">
                <h3 class="text-sm font-bold text-foreground">Pasang Aplikasi RENT SPACE</h3>
                <p class="mt-1 text-[11px] leading-relaxed text-muted-foreground">
                    Akses lebih cepat & dapatkan notifikasi langsung dari layar utama Anda.
                </p>

                <div class="mt-4 flex items-center gap-3">
                    <!-- Android Install Button -->
                    <template x-if="!isIOS">
                        <button @click="install" class="flex-1 rounded-xl bg-primary px-4 py-2 text-xs font-black text-primary-foreground shadow-lg shadow-primary/20 transition-transform active:scale-95">
                            INSTALL SEKARANG
                        </button>
                    </template>

                    <!-- iOS Hint -->
                    <template x-if="isIOS">
                        <div class="flex items-center gap-2 rounded-xl bg-white/5 px-4 py-2 border border-white/10 w-full justify-center">
                            <span class="text-[10px] font-bold text-foreground">Klik <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline mb-1 mx-0.5"><rect width="14" height="14" x="5" y="5" rx="2"/><path d="M12 1v14"/><path d="m9 4 3-3 3 3"/></svg> lalu <span class="text-primary">"Add to Home Screen"</span></span>
                        </div>
                    </template>

                    <button @click="dismiss" class="rounded-xl bg-white/5 px-4 py-2 text-xs font-bold text-foreground border border-white/10 hover:bg-white/10">
                        Nanti Saja
                    </button>
                </div>
            </div>

            <!-- Close Button -->
            <button @click="dismiss" class="absolute top-2 right-2 p-1 text-muted-foreground hover:text-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    </div>
</div>
