<div class="py-2 px-4 sm:px-6 lg:px-8 bg-background min-h-[calc(100vh-4rem)]">
    <style>
        @keyframes progress {
            0% { background-position: 0 0; }
            100% { background-position: 10px 0; }
        }
        @keyframes shine {
            from { left: -100%; }
            to { left: 200%; }
        }
        .badge-shine {
            position: relative;
            overflow: hidden;
        }
        .badge-shine::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: skewX(-20deg);
            animation: shine 3s infinite;
        }
    </style>
    <div class="max-w-3xl mx-auto space-y-8">

        <!-- Header -->
        <!-- <div class="text-center space-y-2">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">Cek Pesanan</h1>
            <p class="text-base text-muted-foreground">
                Masukkan NIK dan Nomor WhatsApp untuk melihat riwayat sewa Anda.
            </p>
        </div> -->
        <div class="text-center mb-2">
            <h2 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">Dashboard Anda</h2>
            <p class="mt-4 text-sm text-muted-foreground">Kelola pesanan dan pengaturan akun Anda di sini.</p>
        </div>

        <!-- Tab Switcher (Segmented Control) -->
        <div class="mb-6">
            <div class="flex bg-muted/60 p-1 rounded-2xl border border-border shadow-inner gap-1 w-full relative">
                <button wire:click="$set('currentTab', 'pesanan')"
                    class="flex-1 flex justify-center items-center gap-2 px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all {{ $currentTab === 'pesanan' ? 'bg-background text-foreground shadow-sm ring-1 ring-border' : 'text-muted-foreground hover:text-foreground hover:bg-background/50' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                        class="sm:w-[16px] sm:h-[16px]">
                        <rect width="20" height="14" x="2" y="5" rx="2" />
                        <line x1="2" x2="22" y1="10" y2="10" />
                    </svg>
                    Pesanan
                </button>
                <button wire:click="$set('currentTab', 'profil')"
                    class="flex-1 flex justify-center items-center gap-2 px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all {{ $currentTab === 'profil' ? 'bg-background text-foreground shadow-sm ring-1 ring-border' : 'text-muted-foreground hover:text-foreground hover:bg-background/50' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                        class="sm:w-[16px] sm:h-[16px]">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Info Sesi
                </button>
            </div>
        </div>

        @if($currentTab === 'pesanan')
            <div class="animate-in fade-in slide-in-from-bottom-2 duration-300 space-y-6">
                @if ($orders !== null)
                    {{-- Compact Summary Stats --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Total Pesanan --}}
                        <div
                            class="bg-card rounded-2xl border border-border p-4 shadow-sm group transition-all hover:bg-muted/30">
                            <span class="text-[10px] font-bold text-muted-foreground/70 block mb-1">Total Transaksi</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl font-black text-foreground">{{ $this->total_orders }}</span>
                                <span class="text-[10px] font-semibold text-muted-foreground lowercase">Transaksi</span>
                            </div>
                        </div>

                        {{-- Total Billing --}}
                        <div
                            class="bg-card rounded-2xl border border-border p-4 shadow-sm group transition-all hover:bg-muted/30">
                            <span class="text-[10px] font-bold text-muted-foreground/70 block mb-1">Total Billing</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-[10px] font-black text-foreground">Rp</span>
                                <span
                                    class="text-xl font-black text-foreground">{{ number_format($this->total_billing, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between pt-4 pb-2 border-b border-border">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-bold text-foreground">Riwayat Pesanan</h2>
                            @if($this->active_rentals_count > 0)
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 animate-in fade-in zoom-in duration-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ $this->active_rentals_count }} Aktif
                                </span>
                            @endif
                        </div>
                        <span class="text-muted-foreground font-medium text-xs">({{ $orders->count() }} transaksi)</span>
                    </div>

                    <div class="space-y-4">

                        @forelse ($orders as $order)
                            @php
                                $statusConfig = [
                                    'pending' => ['class' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-500', 'label' => 'Pending'],
                                    'paid' => ['class' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20', 'dot' => 'bg-blue-500', 'label' => 'Paid'],
                                    'renting' => ['class' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-500', 'label' => 'Rent'],
                                    'completed' => ['class' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-500', 'label' => 'Done'],
                                    'cancelled' => ['class' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-500', 'label' => 'Cancel'],
                                ];
                                $sc = $statusConfig[$order->status] ?? $statusConfig['pending'];
                            @endphp

                            @php
                                $isActiveRental = in_array($order->status, ['paid', 'renting']) && $order->waktu_selesai->isFuture();
                                $selesaiTimestamp = $order->waktu_selesai->timestamp * 1000;
                            @endphp
                            <div x-data="{
                                        expanded: false,
                                        countdown: '',
                                        countdownFull: '',
                                        endTime: {{ $selesaiTimestamp }},
                                        tick() {
                                            const now = Date.now();
                                            const diff = Math.floor((this.endTime - now) / 1000);
                                            if (diff <= 0) { 
                                                this.countdown = 'Selesai'; 
                                                this.countdownFull = 'Waktu Sewa Selesai';
                                                return; 
                                            }
                                            const h = Math.floor(diff / 3600);
                                            const m = Math.floor((diff % 3600) / 60);
                                            const s = diff % 60;

                                            // Short version for list
                                            if (h > 0) this.countdown = h + ' jam ' + m + ' mnt';
                                            else this.countdown = m + ' mnt';

                                            // Full version for expanded
                                            this.countdownFull = h + ' jam ' + m + ' mnt ' + s + ' dtk';
                                        }
                                    }" x-init="{{ $isActiveRental ? 'tick(); setInterval(() => tick(), 1000)' : '' }}"
                                class="bg-card rounded-2xl border shadow-sm overflow-hidden {{ $order->status === 'pending' ? 'border-amber-400/50 dark:border-amber-500/30' : ($isActiveRental ? 'border-emerald-400/50 dark:border-emerald-500/30' : 'border-border') }}">

                                <!-- Row Header (Clickable) -->
                                <div @click="expanded = !expanded"
                                    class="flex items-center gap-3 px-5 py-4 cursor-pointer hover:bg-muted/40 transition-colors group">


                                    <!-- Main Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="text-xs font-black text-primary tracking-tight uppercase">{{ $order->booking_code }}</span>
                                            @if($isActiveRental)
                                                <span
                                                    class="inline-flex items-center gap-1 px-1.5 py-0 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                                    {{ $sc['label'] }}
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 px-1.5 py-0 rounded-full text-[9px] font-bold border {{ $sc['class'] }}">
                                                    <span
                                                        class="w-1 h-1 rounded-full {{ $sc['dot'] }} {{ $order->status === 'pending' ? 'animate-pulse' : '' }}"></span>
                                                    {{ $sc['label'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <p class="text-[10px] text-muted-foreground">
                                                {{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
                                        </div>
                                    </div>

                                    <!-- Total & Pay Button -->
                                    <div class="flex items-center gap-3 shrink-0">
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-foreground">Rp
                                                {{ number_format($order->grand_total, 0, ',', '.') }}</div>
                                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                                {{ $order->waktu_mulai->format('d/m') }} &ndash;
                                                {{ $order->waktu_selesai->format('d/m') }}
                                            </p>
                                        </div>

                                        @if($order->status === 'pending' && $order->metode_pembayaran !== 'cash')
                                            <a href="{{ route('public.payment', $order->booking_code) }}" wire:navigate @click.stop
                                                class="hidden sm:flex items-center gap-1.5 h-9 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all shadow-sm shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <rect width="20" height="14" x="2" y="5" rx="2" />
                                                    <line x1="2" x2="22" y1="10" y2="10" />
                                                </svg>
                                                Bayar
                                            </a>
                                        @endif
                                    </div>

                                    <!-- Chevron -->
                                    <svg :class="expanded ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" width="18"
                                        height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="text-muted-foreground transition-transform duration-300 shrink-0">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>

                                <!-- Expanded Detail -->
                                <div x-show="expanded" x-collapse x-cloak>
                                    <div class="px-5 pb-5 pt-1 border-t border-border space-y-5">

                                        <!-- Units rented -->
                                        <div class="space-y-2 pt-4">
                                            <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Item
                                                yang Disewa</p>
                                            <div class="space-y-2">
                                                @foreach($order->units as $unit)
                                                    <div class="bg-muted/40 rounded-xl px-4 py-3">
                                                        <div>
                                                            <p class="text-sm font-semibold text-foreground leading-tight">
                                                                {{ $unit->seri }}
                                                                    <span class="opacity-50 text-[10px] font-mono">[#{{ str_pad($unit->id, 3, '0', STR_PAD_LEFT) }}]</span>
                                                            </p>
                                                            <p class="text-xs text-muted-foreground">{{ $unit->warna }} &bull;
                                                                {{ $unit->memori }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Time & Cost -->
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-end mt-2">
                                                <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">
                                                    Waktu Sewa</p>
                                                @if($isActiveRental)
                                                    <div class="text-right">
                                                        <p class="text-[9px] font-bold text-muted-foreground uppercase">Sisa Waktu</p>
                                                        <p x-text="countdownFull" class="text-xs font-black text-emerald-600"></p>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="bg-muted/40 rounded-xl px-4 py-3">
                                                    <p class="text-[10px] text-muted-foreground mb-0.5">Mulai</p>
                                                    <p class="text-sm font-semibold text-foreground">
                                                        {{ $order->waktu_mulai->translatedFormat('d M Y') }}</p>
                                                    <p class="text-xs text-muted-foreground">
                                                        {{ $order->waktu_mulai->format('H:i') }}</p>
                                                </div>
                                                <div class="bg-muted/40 rounded-xl px-4 py-3">
                                                    <p class="text-[10px] text-muted-foreground mb-0.5">Selesai</p>
                                                    <p class="text-sm font-semibold text-foreground">
                                                        {{ $order->waktu_selesai->translatedFormat('d M Y') }}</p>
                                                    <p class="text-xs text-muted-foreground">
                                                        {{ $order->waktu_selesai->format('H:i') }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Breakdown -->
                                        <div class="space-y-2">
                                            <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Rincian
                                                Biaya</p>
                                            <div class="bg-muted/40 rounded-xl px-4 py-3 space-y-2">
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-muted-foreground">Subtotal</span>
                                                    <span class="text-foreground">Rp
                                                        {{ number_format($order->subtotal_harga, 0, ',', '.') }}</span>
                                                </div>

                                                @if($order->potongan_diskon > 0)
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-muted-foreground">Diskon</span>
                                                        <span class="text-rose-500">- Rp
                                                            {{ number_format($order->potongan_diskon, 0, ',', '.') }}</span>
                                                    </div>
                                                @endif

                                                @if($order->kode_unik_pembayaran)
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="text-muted-foreground">Kode Unik</span>
                                                        <span class="text-amber-500">+
                                                            {{ number_format($order->kode_unik_pembayaran, 0, ',', '.') }}</span>
                                                    </div>
                                                @endif

                                                <div class="h-px bg-border"></div>

                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-bold text-foreground">Grand Total</span>
                                                    <span class="text-base font-extrabold text-primary">Rp
                                                        {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        @if($order->status == 'pending')
                                            <div class="mt-4 mb-4 space-y-2">
                                                @if(session()->has('success_cancel'))
                                                    <div class="bg-emerald-500/10 text-emerald-600 text-[10px] p-2 rounded text-center">
                                                        {{ session('success_cancel') }}
                                                    </div>
                                                @endif
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button wire:click="cancelOrder('{{ $order->booking_code }}')"
                                                        wire:confirm="Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan."
                                                        class="flex items-center justify-center gap-1.5 h-10 rounded-xl bg-red-600 text-white text-[10px] sm:text-xs font-bold hover:bg-red-700 transition-all shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10" />
                                                            <line x1="15" y1="9" x2="9" y2="15" />
                                                            <line x1="9" y1="9" x2="15" y2="15" />
                                                        </svg>
                                                        Batalkan
                                                    </button>
                                                    @if($order->metode_pembayaran !== 'cash')
                                                        <a href="{{ route('public.payment', $order->booking_code) }}" wire:navigate
                                                            class="flex items-center justify-center gap-1.5 h-10 rounded-xl bg-primary text-primary-foreground text-[10px] sm:text-xs font-bold hover:bg-primary/90 transition-all shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <rect width="20" height="14" x="2" y="5" rx="2" />
                                                            <line x1="2" x2="22" y1="10" y2="10" />
                                                        </svg>
                                                            Bayar
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="bg-card rounded-2xl border border-dashed border-border p-10 text-center space-y-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                    class="pt-4 mx-auto text-muted-foreground/40">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" x2="8" y1="13" y2="13" />
                                    <line x1="16" x2="8" y1="17" y2="17" />
                                    <polyline points="10 9 9 9 8 9" />
                                </svg>
                                <p class="text-sm font-semibold text-foreground">Tidak ada pesanan ditemukan</p>
                                <p class="pb-4 text-xs text-muted-foreground">Pastikan NIK dan Nomor WA sudah sama persis dengan
                                    yang digunakan saat sewa.</p>
                            </div>
                        @endforelse
                    </div>
                @endif
        @elseif($currentTab === 'profil')
                <div class="animate-in fade-in slide-in-from-bottom-2 duration-300 space-y-6">
                    @php 
                        $firstOrder = $orders ? $orders->first() : null;
                        $ltv = $this->ltv;
                        $tier = $this->tier;
                        $nextTier = $this->nextTier;
                    @endphp

                    {{-- Premium Rank Progress --}}
                    @if($tier)
                        <div class="bg-card border border-border rounded-3xl overflow-hidden shadow-sm p-6 space-y-6 animate-in fade-in slide-in-from-bottom-3 duration-500">
                            <!-- Header Info -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em] mb-2">Pangkat Anda</h4>
                                    <div class="flex items-center gap-2">
                                        <div class="relative group">
                                            <div class="absolute -inset-1 bg-gradient-to-r from-primary/50 to-violet-500/50 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                                            <span class="relative inline-flex items-center rounded-full border px-4 py-1 text-[10px] font-black uppercase tracking-widest {{ $tier->color }} shadow-md badge-shine">
                                                {{ $tier->label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em] mb-1">Total Belanja</h4>
                                    <p class="text-xl font-black text-foreground">
                                        <span class="text-xs font-medium text-muted-foreground mr-0.5">Rp</span>{{ number_format($ltv, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Integrated Gamified Roadmap & Progress -->
                            <div class="space-y-12 pt-4">
                                <div class="flex items-center justify-between px-1">
                                    <h4 class="text-[10px] font-bold text-muted-foreground tracking-tight">Perjalanan Pangkat</h4>
                                    @if($nextTier)
                                        <p class="text-[10px] font-bold text-primary">Sisa Rp {{ number_format($nextTier->threshold - $ltv, 0, ',', '.') }} lagi untuk mencapai {{ $nextTier->label }}</p>
                                    @endif
                                </div>

                                <div class="relative flex items-center gap-0 overflow-x-auto pb-10 pt-12 hide-scrollbar snap-x scroll-smooth">
                                    @php
                                        $tiers = \App\Helpers\CustomerHelper::tiers();
                                        $totalTiers = count($tiers);
                                    @endphp

                                    @foreach($tiers as $index => $t)
                                        @php 
                                            $isAchieved = $ltv >= $t['threshold'];
                                            $isCurrent = $tier->label === $t['label'];
                                            $nextT = ($index + 1 < $totalTiers) ? $tiers[$index + 1] : null;
                                            
                                            // Calculate progress to next dot for the line segment
                                            $segmentProgress = 0;
                                            if ($isAchieved && $nextT) {
                                                if ($ltv >= $nextT['threshold']) {
                                                    $segmentProgress = 100;
                                                } else {
                                                    $currentRange = $nextT['threshold'] - $t['threshold'];
                                                    $currentProgress = $ltv - $t['threshold'];
                                                    $segmentProgress = ($currentProgress / $currentRange) * 100;
                                                }
                                            }
                                        @endphp
                                        <div class="snap-center shrink-0 w-32 flex flex-col items-center relative z-10">
                                            <!-- Line Segments (Fused with Dots) -->
                                            <div class="absolute top-[0.625rem] left-0 w-full h-2 flex z-0">
                                                <!-- Left side of dot (Coming from previous) -->
                                                <div class="h-full w-1/2 {{ $index === 0 ? 'bg-transparent' : $t['color'] }} {{ $isAchieved ? 'opacity-100 shadow-[0_0_15px_rgba(var(--primary-rgb),0.4)]' : 'opacity-25' }} border-none transition-all duration-700"></div>
                                                
                                                <!-- Right side of dot (Going to next) -->
                                                <div class="h-full w-1/2 relative {{ $index === $totalTiers - 1 ? 'bg-transparent' : ($nextT ? $nextT['color'] : 'bg-muted') }} {{ $isNextAchieved ?? false ? 'opacity-100 shadow-[0_0_15px_rgba(var(--primary-rgb),0.4)]' : 'opacity-15' }} transition-all duration-700">
                                                    @if($segmentProgress > 0)
                                                        <!-- Actual Active Progress on this segment -->
                                                        <div class="absolute inset-y-0 left-0 {{ $nextT ? $nextT['color'] : 'bg-primary' }} opacity-100 shadow-[0_0_20px_rgba(var(--primary-rgb),0.5)] transition-all duration-1000 overflow-hidden badge-shine" style="width: {{ $segmentProgress }}%">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Milestone Dot -->
                                            <div class="relative flex items-center justify-center h-7 w-7 z-20">
                                                @if($isCurrent)
                                                    <div class="absolute h-12 w-12 {{ $t['color'] }} opacity-40 rounded-full animate-pulse blur-xl"></div>
                                                @endif
                                                <div class="h-6 w-6 rounded-full border-[4px] transition-all duration-700 shadow-[0_0_20px_rgba(0,0,0,0.3)] {{ $t['color'] }} {{ $isAchieved ? 'border-background ring-4 ring-foreground/5' : 'opacity-40 border-background grayscale-[0.3]' }}">
                                                </div>
                                            </div>

                                            <!-- Badge -->
                                            <div class="mt-5 transition-all duration-1000 {{ $isAchieved ? 'opacity-100 scale-100' : 'opacity-60 scale-90' }}">
                                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-[8px] font-black {{ $t['color'] }} {{ $isAchieved ? 'badge-shine shadow-md' : 'border-dashed opacity-40' }}">
                                                    {{ $t['label'] }}
                                                </span>
                                            </div>

                                            <!-- Threshold Info -->
                                            <div class="mt-2 text-center">
                                                <p class="text-[9px] font-bold {{ $isAchieved ? 'text-foreground' : 'text-muted-foreground/30' }} tracking-tight">
                                                    {{ $index === 0 ? 'Mulai' : 'Rp ' . number_format($t['threshold'] / 1000, 0, ',', '.') . 'k' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Simple Shadcn-style Profile Card --}}
                    <div class="bg-card border border-border rounded-3xl overflow-hidden shadow-sm">
                        <div class="bg-muted/30 px-6 py-5 border-b border-border flex items-center justify-between">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-lg text-foreground leading-none">{{ $firstOrder?->nama ?? 'Akun Peminjam' }}</h3>
                                    @if($tier)
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-tighter {{ $tier->color }} badge-shine">
                                            {{ $tier->label }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-muted-foreground font-medium mt-1.5">Sesi Identitas Aktif Tersimpan</p>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round" class="text-emerald-500">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                        </div>

                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-[10px] md:text-xs font-bold text-muted-foreground block mb-1">Nomor
                                        Identitas (NIK)</label>
                                    <p class="text-md font-bold text-foreground ">{{ $nik }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] md:text-xs font-bold text-muted-foreground block mb-1">Nomor
                                        WhatsApp</label>
                                    <p class="text-md font-bold text-foreground ">{{ $no_wa }}</p>
                                </div>
                            </div>

                            @if($firstOrder && $firstOrder->alamat)
                                <div class="pt-4 border-t border-border">
                                    <label class="text-[10px] md:text-xs font-bold text-muted-foreground block mb-1">Alamat
                                        Domisili Terdaftar</label>
                                    <p class="text-sm font-medium text-foreground leading-relaxed">{{ $firstOrder->alamat }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="pt-2">
                        <a href="{{ route('customer.logout') }}" wire:navigate
                            wire:confirm="Apakah Anda yakin ingin mengeluarkan sesi ini?"
                            class="w-full flex justify-center items-center gap-2 rounded-2xl bg-red-500/10 text-red-500 border border-red-500/20 text-sm font-bold px-4 py-3.5 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" x2="9" y1="12" y2="12" />
                            </svg>
                            Keluar dari Sesi Ini
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>