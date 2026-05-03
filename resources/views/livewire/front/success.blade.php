<div class="min-h-screen bg-background py-4 px-4 sm:py-16 flex flex-col items-center justify-start sm:justify-center" @if($rental->status === 'pending') wire:poll.15s="refreshStatus" @endif>
    <div id="invoice-content" class="w-full max-w-md mx-auto bg-card border border-border rounded-2xl shadow-sm overflow-hidden">

        <!-- Header -->
        <div class="p-6 text-center border-b border-border/50 bg-muted/20">
            @if($rental->status === 'cancelled')
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-500/10 text-rose-500 mb-4">
                    <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round" />
                        <line x1="15" y1="9" x2="9" y2="15" stroke-linecap="round" stroke-linejoin="round" />
                        <line x1="9" y1="9" x2="15" y2="15" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-foreground">Pesanan Dibatalkan</h1>
                <p class="text-[10px] text-rose-500/70 mt-1 font-medium italic">Status: Sesi Pembayaran Berakhir (Unit Dilepas)</p>
            @elseif($rental->status === 'paid')
                <h1 class="text-xl font-bold tracking-tight text-foreground text-emerald-600">Terima Kasih!</h1>
                <p class="text-[10px] text-emerald-600/70 font-medium mt-1">Status: Siap Diambil / Lunas</p>
            @elseif($rental->status === 'renting')
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-500/10 text-emerald-500 mb-4 animate-in zoom-in duration-500">
                    <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-foreground text-emerald-600">Unit Sedang Disewa</h1>
                <p class="text-[10px] text-emerald-600/70 font-medium mt-1">Status: Aktif / Dibawa Penyewa</p>
            @elseif($rental->metode_pembayaran === 'cash' && $rental->status === 'pending')
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-blue-500/10 text-blue-500 mb-4 animate-in zoom-in duration-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-foreground text-blue-600">Pesanan Diterima!</h1>
                <p class="text-[11px] text-blue-600/80 font-semibold mt-1">Selesaikan Pembayaran di lokasi</p>
            @else
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-500/10 text-amber-500 mb-4">
                    <div class="w-7 h-7 border-4 border-amber-500/30 border-t-amber-500 rounded-full animate-spin"></div>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-foreground">Menunggu Pembayaran</h1>
                
                 @php
                    // --- JURUS ANTI-ZONA WAKTU (SINKRON 15 MENIT) ---
                    $secondsRemaining = 900 - (now()->timestamp - $rental->created_at->timestamp);
                    if ($secondsRemaining < 0) $secondsRemaining = 0;
                 @endphp

                <div wire:ignore x-data="{
                    seconds: {{ $secondsRemaining }},
                    timeLeft: '--m --d',
                    status: 'green',
                    update() {
                        if (this.seconds <= 0) { 
                            this.timeLeft = 'Waktu habis'; 
                            this.status = 'red'; 
                            // Kita biarkan poller mereduce status ke cancelled
                            return; 
                        }
                        
                        const h = Math.floor(this.seconds / 3600);
                        const m = Math.floor((this.seconds % 3600) / 60);
                        const s = Math.floor(this.seconds % 60);
                        
                        this.timeLeft = (h > 0 ? h + 'j ' : '') + (m > 0 || h > 0 ? m + 'm ' : '') + s + 'd';
                        this.status = this.seconds < 10 ? 'red' : (this.seconds < 30 ? 'amber' : 'green');
                        this.seconds--;
                    }
                }" x-init="update(); setInterval(() => update(), 1000)" class="mt-2 flex flex-col items-center min-h-[60px] justify-center no-pdf">
                    <span class="text-[9px] text-muted-foreground mb-0.5 uppercase font-bold tracking-widest">Batas Waktu Pembayaran</span>
                    <div x-text="timeLeft" 
                        class="text-3xl font-black font-mono tracking-tighter transition-all duration-500 min-w-[120px] text-center"
                        :class="{
                            'text-emerald-500': status === 'green',
                            'text-amber-500': status === 'amber',
                            'text-red-500 animate-pulse': status === 'red'
                        }">
                    </div>
                </div>
            @endif

            <p class="text-xs text-muted-foreground mt-3 font-medium">{{ $rental->nama }} &bull; <span class="bg-muted px-1.5 py-0.5 rounded text-foreground font-bold tracking-tight">{{ $rental->booking_code }}</span></p>

            @if($debugError && auth()->check() && auth()->user()->role === 'admin')
                <div class="mt-4 p-2 bg-red-500/10 border border-red-500/20 rounded text-[10px] text-red-600 font-mono text-left no-pdf">
                    Debug Error: {{ $debugError }}
                </div>
            @endif
        </div>

        <div class="px-6 pb-6 pt-3 space-y-3.5">
            <!-- Units -->
            <div class="space-y-2.5">
                <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.1em]">Item Yang Disewa</p>
                @foreach($rental->units as $unit)
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-foreground">
                            {{ $unit->seri }}
                                <span class="opacity-50 text-[10px] font-mono">[#{{ str_pad($unit->id, 3, '0', STR_PAD_LEFT) }}]</span>
                        </span>
                        <span class="text-xs text-muted-foreground">{{ $unit->warna }} &bull; {{ $unit->memori }}</span>
                    </div>
                @endforeach
                @if($rental->status == 'pending' && $rental->metode_pembayaran != 'cash')
                    <div class="mt-4 pt-4 border-t border-dashed border-border/50 no-pdf">
                        <a href="{{ route('public.payment', $rental->booking_code) }}?change=1" 
                            class="text-[10px] font-bold text-primary hover:underline uppercase tracking-widest">
                            ← Ubah Metode Pembayaran
                        </a>
                    </div>
                @endif
            </div>

            <div class="h-px bg-border/50"></div>

            <!-- Schedule -->
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.1em] mb-1.5">Pengambilan</p>
                    <p class="font-bold text-foreground leading-none">{{ $rental->waktu_mulai->format('d M Y') }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ $rental->waktu_mulai->format('H:i') }} WIB</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.1em] mb-1.5">Kembali</p>
                    <p class="font-bold text-primary leading-none">{{ $rental->waktu_selesai->format('d M Y') }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ $rental->waktu_selesai->format('H:i') }} WIB</p>
                </div>
            </div>

            <div class="h-px bg-border/50"></div>

            <!-- Price Breakdown -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-muted-foreground text-xs">
                    <span>Metode Pembayaran</span>
                    <span class="font-medium">{{ strtoupper($rental->metode_pembayaran ?? 'Online') }}</span>
                </div>
                <div class="flex justify-between text-muted-foreground text-xs">
                    <span>Subtotal Harga</span>
                    <span class="font-medium">Rp {{ number_format($rental->subtotal_harga, 0, ',', '.') }}</span>
                </div>
                @if($rental->potongan_diskon > 0)
                    <div class="flex justify-between text-rose-500 text-xs">
                        <span>Potongan Diskon</span>
                        <span class="font-medium">- Rp {{ number_format($rental->potongan_diskon, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($this->rental->kode_unik_pembayaran)
                    <div class="flex justify-between text-amber-600 text-xs">
                        <span>Kode Unik</span>
                        <span class="font-medium">+ {{ $this->rental->kode_unik_pembayaran }}</span>
                    </div>
                @endif
                @php 
                    $details = $this->rental->payment_details;
                    $paymentFee = is_array($details) ? ($details['payment_fee'] ?? 0) : data_get($details, 'payment_fee', 0);
                    $paymentFeeLabel = is_array($details) ? ($details['payment_fee_label'] ?? '') : data_get($details, 'payment_fee_label', '');
                @endphp

                @if($paymentFee > 0)
                    <div class="flex justify-between text-muted-foreground text-[10px] uppercase font-bold">
                        <span>Biaya Layanan <span class="text-zinc-500 font-medium ml-1">{{ $paymentFeeLabel }}</span></span>
                        <span class="font-bold text-foreground">+ Rp {{ number_format($paymentFee, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center pt-2 mt-1 border-t border-dashed border-border/60">
                    <span class="font-bold">{{ $rental->metode_pembayaran === 'cash' ? 'Total Tunai' : 'Total Tagihan' }}</span>
                    <span class="text-xl font-black text-foreground">Rp {{ number_format($rental->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Conditional Actions Based on Ownership and Status -->
            @if($isOwner)
                <div class="space-y-3 pt-4 no-pdf">
                    @if($rental->status === 'pending')
                        {{-- Jika Online, kasih tombol Bayar Sekarang --}}
                        @if($rental->metode_pembayaran !== 'cash')
                            <a href="{{ route('public.payment', $rental->booking_code) }}"
                                class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-zinc-900 text-white text-sm font-bold transition-all active:scale-95 shadow-lg shadow-zinc-900/10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h7"/><path d="M16 19h6"/><path d="M19 16v6"/><rect width="7" height="5" x="14" y="11" rx="1"/><path d="M3 10h18"/></svg>
                                Bayar Sekarang
                            </a>
                            <div class="text-center mt-3">
                                <a href="{{ route('public.payment', ['booking_code' => $rental->booking_code, 'change' => 1]) }}"
                                   class="text-[10px] font-bold text-muted-foreground hover:text-foreground transition-all uppercase tracking-widest border-b border-muted hover:border-foreground pb-0.5">
                                   Ganti Metode Pembayaran
                                 </a>
                            </div>
                        @endif

                        {{-- Jika Cash, tetap tombol WA --}}
                        @if($rental->metode_pembayaran === 'cash')
                            {{-- Tombol konfirmasi WA dihapus agar alur murni via email otomatis --}}
                            <p class="text-[10px] text-center text-muted-foreground px-4 italic bg-muted/30 py-3 rounded-xl border border-dashed border-border/50">Admin telah menerima laporan pesanan Anda. Silakan datang ke lokasi sesuai jadwal.</p>
                        @else
                            <p class="text-[10px] text-center text-muted-foreground px-4">Setelah membayar, halaman ini akan otomatis berubah menjadi struk berhasil.</p>
                        @endif
                    @elseif($rental->status === 'paid')
                        <!-- Success Notifications -->
                        {{-- Peringatan screenshot dihapus karena digantikan sistem notifikasi email otomatis --}}
                        {{-- Tombol konfirmasi WA lunas dihapus --}}
                        <p class="text-[10px] text-center text-emerald-600/70 px-4 font-medium italic">Invoice digital telah dikirimkan ke email Anda.</p>
                    @else
                        <!-- Cancelled Case -->
                        <a href="{{ route('public.booking') }}" wire:navigate
                            class="w-full flex items-center justify-center gap-2 h-12 rounded-xl bg-zinc-100 text-zinc-900 text-sm font-bold border border-zinc-200 hover:bg-zinc-200 transition-all">
                            Sewa Unit Lain
                        </a>
                    @endif


                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <a href="{{ route('public.check-order') }}" wire:navigate
                            class="flex items-center justify-center gap-1.5 h-11 rounded-xl bg-secondary text-secondary-foreground hover:bg-secondary/80 text-xs font-semibold transition-all border border-border/50">
                            Cek Pesanan
                        </a>
                        <a href="/" wire:navigate
                            class="flex items-center justify-center gap-1.5 h-11 rounded-xl bg-secondary text-secondary-foreground hover:bg-secondary/80 text-xs font-semibold transition-all border border-border/50">
                            Beranda
                        </a>
                    </div>
                </div>
            @endif

            <!-- Admin Panel (Keep as requested) -->
            @if(auth()->check() && auth()->user()->role === 'admin')
                @if($rental->metode_pembayaran == 'online' && $rental->status === 'pending')
                    <div class="py-10 flex flex-col items-center justify-center text-center px-4 no-pdf">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary animate-pulse"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-foreground">Metode Belum Dipilih</h3>
                        <p class="text-[10px] text-muted-foreground mt-1 max-w-[200px]">Silakan klik tombol di bawah untuk memilih bank atau QRIS.</p>
                    </div>
                @elseif($rental->metode_pembayaran != 'cash' && $rental->status === 'pending')
                    <!-- VA Detail -->
                    <div class="flex flex-col items-center p-6 bg-muted/20 no-pdf">
                        <p class="text-[10px] uppercase font-bold tracking-widest text-muted-foreground mb-1">{{ str_replace('_', ' ', $rental->metode_pembayaran) }}</p>
                        @if(isset($rental->payment_details['va_numbers']))
                            <p class="text-2xl font-mono font-bold tracking-tighter">{{ $rental->payment_details['va_numbers'][0]['va_number'] }}</p>
                        @endif
                    </div>
                @endif
                <div class="pt-6 border-t border-border mt-6 space-y-3 no-pdf">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest leading-none">Admin Control</p>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $rental->status === 'paid' ? 'bg-emerald-500/10 text-emerald-600' : ($rental->status === 'cancelled' ? 'bg-rose-500/10 text-rose-600' : 'bg-amber-500/10 text-amber-600') }}">
                            #{{ strtoupper($rental->status) }}
                        </span>
                    </div>

                    @if(session()->has('admin_message'))
                        <div class="bg-emerald-500/10 text-emerald-600 text-[10px] p-2 rounded-lg text-center font-bold">
                            {{ session('admin_message') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-2">
                        @if($rental->status === 'pending')
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="validateOrder" wire:confirm="Validasi pembayarn manual?"
                                    class="flex items-center justify-center h-10 rounded-xl bg-zinc-900 text-white text-[11px] font-bold hover:bg-black transition-all">
                                    Lunas
                                </button>
                                <button wire:click="cancelOrder" wire:confirm="Apakah Anda yakin ingin membatalkan pesanan ini?"
                                    wire:loading.attr="disabled"
                                    class="flex items-center justify-center h-10 rounded-xl bg-rose-600 text-white text-[11px] font-bold hover:bg-rose-700 transition-all shadow-sm disabled:opacity-50">
                                    Batalkan Pesanan
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <p class="text-[10px] text-muted-foreground text-center pt-6 opacity-40">
                &copy; {{ date('Y') }} Rent Space &bull; Tanda Terima Digital
            </p>
        </div>
    </div>
    <!-- Feedback Modal with 4s Delay -->
    @if($showFeedbackModal)
    <div x-data="{ showDelayed: false }" x-init="setTimeout(() => showDelayed = true, 4000)">
        <div x-show="showDelayed" 
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm no-pdf"
            x-cloak>
            <div class="bg-card w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-border p-6 animate-in zoom-in-95 slide-in-from-bottom-10 duration-500">
            <div class="text-center">
                <div class="w-20 h-20 bg-amber-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                </div>
                <h3 class="text-2xl font-bold tracking-tight text-foreground ">Gimana Servis Kami?</h3>
                <p class="text-xs text-muted-foreground mt-2 px-4 leading-relaxed">Puas sama layanannya? Kasih bintang & feedback ya biar kami makin semangat!</p>
            </div>

            <div class="mt-8">
                <!-- Star Rating -->
                <div class="flex justify-center gap-2.5 mb-8">
                    @for($i = 1; $i <= 5; $i++)
                    <button wire:click="$set('rating', {{ $i }})" class="transition-all active:scale-75 hover:scale-110">
                        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" 
                            fill="{{ $rating >= $i ? 'currentColor' : 'none' }}" 
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" 
                            class="{{ $rating >= $i ? 'text-amber-500' : 'text-zinc-300 dark:text-zinc-700' }}">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </button>
                    @endfor
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest ml-1">Testimoni Kamu</label>
                    <textarea wire:model="feedback" 
                        placeholder="Tulis kesan & pesan kamu di sini..."
                        class="w-full min-h-[120px] rounded-[1.5rem] border border-border bg-muted/30 p-5 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500/30 transition-all outline-none resize-none"></textarea>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-3">
                <button wire:click="submitFeedback" 
                    class="h-14 w-full bg-zinc-950 dark:bg-white text-white dark:text-zinc-950 rounded-2xl font-bold text-sm shadow-xl shadow-zinc-950/20 active:scale-95 transition-all">
                    Kirim Review
                </button>
                <button wire:click="skipFeedback" class="h-10 w-full text-xs text-muted-foreground font-bold hover:text-foreground transition-colors uppercase tracking-widest">
                    Nanti Saja
                </button>
            </div>
        </div>
    </div>
</div>
@endif
</div>