<div>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <div class="py-0 bg-background min-h-[calc(100vh-4rem)]" 
        x-data="{ 
            html5QrCode: null,
            scanning: false,
            hasInteracted: false,
            errorMessage: '',
            facingMode: 'environment',
            
            async startScan() {
                try {
                    this.hasInteracted = true;
                    this.errorMessage = '';
                    this.html5QrCode = new Html5Qrcode('reader');
                    const config = { 
                        fps: 20
                    };
                    
                    this.scanning = true;
                    await this.html5QrCode.start(
                        { facingMode: this.facingMode }, 
                        config, 
                        (decodedText) => {
                            this.scanning = false;
                            this.errorMessage = '';
                            
                            try { if (navigator.vibrate) navigator.vibrate(100); } catch(e){}
                            
                            this.html5QrCode.stop().then(() => {
                                $wire.findUnit(decodedText);
                            });
                        }
                    );
                } catch (err) {
                    this.scanning = false;
                    this.errorMessage = 'Gagal mengakses kamera. Error: ' + err;
                }
            },
            async toggleCamera() {
                try {
                    this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
                    if (this.html5QrCode && this.html5QrCode.getState() === 2) {
                        await this.html5QrCode.stop().catch(() => {});
                    }
                    this.startScan();
                } catch(e) {
                    console.error('Gagal ganti kamera:', e);
                }
            },
            async retry() {
                this.errorMessage = '';
                try {
                    if (this.html5QrCode && this.html5QrCode.getState() === 2) {
                        await this.html5QrCode.stop().catch(() => {});
                    }
                } catch (e) {}
                this.hasInteracted = false;
                $wire.resetScan();
            },
            async stopScan() {
                try {
                    if (this.html5QrCode && this.html5QrCode.getState() === 2) {
                        await this.html5QrCode.stop().catch(err => console.error(err));
                    }
                    this.scanning = false;
                } catch(e) {}
            }
        }"
        x-on:livewire:navigating.window="stopScan()">
        
        <div class="max-w-2xl mx-auto pt-0 space-y-6">
            <!-- Header (Matched with Unit Manager) -->
            <div class="flex items-center justify-between">
                <div class="">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Quick Scanner</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Scan unit untuk validasi serah terima fisik secara instan.</p>
                </div>
                @if($scannedUnit)
                    <button @click="retry()" class="p-2 rounded-lg border hover:bg-muted transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                @endif
            </div>

            <!-- Scanner View (Hides completely when unit scanned) -->
            @if(!$scannedUnit)
                <div class="relative rounded-2xl border bg-black shadow-sm overflow-hidden aspect-square sm:aspect-[4/3] flex items-center justify-center">
                    <div id="reader" class="w-full h-full object-cover"></div>
                    
                    <!-- interaction Overlay (Simple style) -->
                    <div x-show="!hasInteracted" class="absolute inset-0 z-20 bg-background flex flex-col items-center justify-center p-8 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center text-muted-foreground mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2"/><rect width="7" height="7" x="7" y="7" rx="1"/><path d="M10 17h.01"/><path d="M17 10h.01"/><path d="M17 17h.01"/></svg>
                        </div>
                        <h3 class="text-base font-bold mb-1">Siap Scann Unit?</h3>
                        <p class="text-[11px] text-muted-foreground mb-10 max-w-[200px]">Aktifkan kamera untuk mulai manajemen serah terima fisik unit.</p>
                        
                        <button @click="startScan()" class="h-11 px-10 rounded-md bg-primary text-primary-foreground font-bold text-xs uppercase tracking-wider hover:opacity-90 transition-all">
                            Aktifkan Kamera
                        </button>
                    </div>

                    <!-- Overlay Laser & Mask (While Scanning) -->
                    <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-4" x-show="scanning">
                        <!-- Switch Camera Button (Actual clickable area) -->
                        <div class="absolute top-4 right-4 z-20 pointer-events-auto">
                            <button @click="toggleCamera()" class="p-2.5 rounded-full bg-black/40 backdrop-blur-md border border-white/20 text-white shadow-xl active:scale-90 transition-all hover:bg-black/60">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M21 21v-5h-5"/></svg>
                            </button>
                        </div>

                        <div class="relative w-2/3 aspect-square max-w-[280px]">
                            <!-- The Mask (Black Transparent outside) -->
                            <div class="absolute inset-0 rounded-xl shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]"></div>
                            
                            <!-- Scanner Corners (Perfectly aligned with mask) -->
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-[2.5px] border-l-[2.5px] border-primary rounded-tl-xl z-10"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-[2.5px] border-r-[2.5px] border-primary rounded-tr-xl z-10"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-[2.5px] border-l-[2.5px] border-primary rounded-bl-xl z-10"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-[2.5px] border-r-[2.5px] border-primary rounded-br-xl z-10"></div>
                            
                            <!-- Laser Line -->
                            <div class="absolute inset-x-0 h-[1.8px] bg-primary animate-[scan_2.5s_ease-in-out_infinite] z-10 shadow-[0_0_8px_rgba(var(--primary),0.5)]"></div>
                        </div>
                    </div>

                    <!-- Error State -->
                    <template x-if="errorMessage">
                        <div class="absolute inset-0 bg-background flex flex-col items-center justify-center p-6 text-center">
                            <p class="text-xs font-medium text-destructive mb-4" x-text="errorMessage"></p>
                            <button @click="retry()" class="h-9 px-4 rounded-md bg-muted text-foreground text-[10px] font-bold">
                                COBA LAGI
                            </button>
                        </div>
                    </template>
                </div>
            @endif

            <!-- Unit Info Card -->
            @if($scannedUnit)
                <div class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                    @if(session()->has('message'))
                        <div class="mb-4 flex items-center gap-2 p-3 rounded-lg bg-green-500/10 border border-green-500/20 text-green-600 text-[10px] font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ session('message') }}
                        </div>
                    @endif

                    <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                        <div class="p-6 space-y-6">
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest">{{ $scannedUnit->category?->name ?? 'Mobile' }}</span>
                                        @if($activeRental && in_array($activeRental->status, ['paid', 'confirmed']))
                                            <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                                        @endif
                                    </div>
                                    <h2 class="text-3xl font-black tracking-tight leading-none italic">{{ $scannedUnit->seri }}</h2>
                                    <p class="text-[10px] text-muted-foreground font-medium">S/N: {{ $scannedUnit->imei }}</p>
                                </div>
                                <div class="px-3 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border transition-colors {{ $activeRental && $activeRental->status === 'active' ? 'bg-blue-500/10 text-blue-600 border-blue-500/20' : ($activeRental ? 'bg-orange-500/10 text-orange-600 border-orange-500/20' : 'bg-green-500/10 text-green-600 border-green-500/20') }}">
                                    {{ $activeRental && $activeRental->status === 'active' ? 'In Hands' : ($activeRental ? 'Picked Up' : 'Available') }}
                                </div>
                            </div>

                            @if($activeRental)
                                <div class="space-y-4 pt-4 border-t border-dashed">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-xs font-bold">{{ substr($activeRental->nama, 0, 1) }}</div>
                                            <div>
                                                <p class="text-[9px] font-bold text-muted-foreground uppercase opacity-50">Customer</p>
                                                <p class="text-sm font-bold">{{ $activeRental->nama }}</p>
                                            </div>
                                        </div>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $activeRental->no_wa) }}" target="_blank" class="h-10 w-10 rounded-lg bg-green-500/10 text-green-600 flex items-center justify-center border border-green-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        </a>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="p-3 rounded-lg bg-muted/40 transition-colors">
                                            <p class="text-[8px] font-bold text-muted-foreground uppercase mb-1">Due To</p>
                                            <p class="text-xs font-bold">{{ $activeRental->waktu_selesai->format('d M, H:i') }}</p>
                                        </div>
                                        <div class="p-3 rounded-lg bg-muted/40 transition-colors">
                                            <p class="text-[8px] font-bold text-muted-foreground uppercase mb-1">Payment</p>
                                            <p class="text-xs font-bold text-green-600">{{ in_array($activeRental->status, ['paid', 'active', 'completed']) ? 'Settled' : 'Unpaid' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-3 pt-4">
                                @if($activeRental && in_array($activeRental->status, ['paid', 'confirmed']))
                                    <button wire:click="confirmHandover({{ $activeRental->id }})" class="h-12 w-full flex items-center justify-center bg-primary text-primary-foreground font-black text-xs rounded-lg uppercase tracking-widest active:scale-95 transition-all">
                                        Validasi Serah Terima
                                    </button>
                                @endif
                                <div class="flex gap-3">
                                    <button @click="retry()" class="h-11 flex-1 border bg-background font-bold text-[10px] uppercase tracking-widest rounded-lg hover:bg-muted transition-colors">
                                        Scan Lagi
                                    </button>
                                    @if($activeRental)
                                        <a href="{{ route('admin.transactions') }}?search={{ $activeRental->booking_code }}" wire:navigate class="h-11 px-5 border bg-background flex items-center justify-center font-bold text-[10px] uppercase tracking-widest rounded-lg hover:bg-muted transition-colors">
                                            Detail Booking
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <style>
            @keyframes scan {
                0% { top: 0; }
                50% { top: 100%; }
                100% { top: 0; }
            }
            #reader video {
                object-fit: cover !important;
                width: 100% !important;
                height: 100% !important;
                border-radius: 1rem;
            }
        </style>
    </div>
</div>
