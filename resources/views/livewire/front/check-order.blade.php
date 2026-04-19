<div class="py-2 px-4 sm:px-6 lg:px-8 bg-background min-h-[calc(100vh-4rem)]">
    <div class="max-w-3xl mx-auto space-y-8">

        <!-- Header -->
        <!-- <div class="text-center space-y-2">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">Cek Pesanan</h1>
            <p class="text-base text-muted-foreground">
                Masukkan NIK dan Nomor WhatsApp untuk melihat riwayat sewa Anda.
            </p>
        </div> -->
        <div class="{{ $orders !== null && $orders->count() >= 0 ? 'hidden sm:block' : '' }} text-center mb-5">
            <h2 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">Cek Pesanan</h2>
            <p class="mt-4 text-muted-foreground">Masukkan NIK dan Nomor WhatsApp untuk melihat riwayat sewa Anda.</p>
        </div>

        <!-- Search Card: hidden on mobile when results found -->
        <div class="{{ $orders !== null && $orders->count() >= 0 ? 'hidden sm:block' : '' }} bg-card rounded-2xl border border-border shadow-sm p-6 space-y-4">
            <form wire:submit="search" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="nik" class="text-sm font-semibold text-foreground">NIK (Sesuai KTP)</label>
                        <input wire:model="nik" type="text" id="nik" placeholder="Contoh: 33021..."
                            class="flex h-11 w-full rounded-xl border border-input bg-background px-4 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-all">
                        @error('nik') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="no_wa" class="text-sm font-semibold text-foreground">Nomor WhatsApp</label>
                        <input wire:model="no_wa" type="text" id="no_wa" placeholder="Contoh: 0812..."
                            class="flex h-11 w-full rounded-xl border border-input bg-background px-4 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-all">
                        @error('no_wa') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 h-11 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 transition-all shadow-sm disabled:opacity-60 group">
                    <span wire:loading.remove>Cek Sekarang</span>
                    <span wire:loading>Memuat...</span>
                    <svg wire:loading.remove xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                        class="transition-transform group-hover:scale-110">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </button>
            </form>

            @if (session()->has('error'))
                <div class="p-4 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-sm font-medium flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                        <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Mobile Identity Summary (shown instead of form when results found) -->
        @if ($orders !== null && $orders->count() >= 0)
            @php $firstOrder = $orders->first(); @endphp
            <div class="sm:hidden bg-card rounded-2xl border border-border shadow-sm p-4 flex items-center gap-4">
                <!-- Avatar -->
                <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-base font-extrabold">
                    {{ $firstOrder ? strtoupper(substr($firstOrder->nama, 0, 1)) : '?' }}
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-foreground truncate">{{ $firstOrder?->nama ?? 'Tidak Ditemukan' }}</p>
                    <p class="text-xs text-muted-foreground truncate">NIK: {{ $nik }}</p>
                    <p class="text-xs text-muted-foreground truncate">WA: {{ $no_wa }}</p>
                </div>
                <!-- Reset Button -->
                <button type="button" wire:click="resetSearch" class="shrink-0 flex items-center gap-1.5 text-xs font-semibold text-muted-foreground hover:text-primary transition-colors px-2 py-1.5 rounded-lg hover:bg-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                    Cari Ulang
                </button>
            </div>
        @endif




        <!-- Results -->
        @if ($orders !== null)
            <div class="space-y-4">

                <!-- Spending Summary — compact 1-line bar -->
                @php
                    $totalSpent = $orders->sum('grand_total');
                    $activeOrders = $orders->where('status', 'paid')->filter(fn($o) => $o->waktu_selesai->isFuture())->count();
                @endphp
                <div class="flex items-center gap-2 flex-wrap bg-muted/50 rounded-xl px-4 py-2.5 text-xs font-medium text-muted-foreground border border-border">
                    <span>📦 <span class="font-bold text-foreground">{{ $orders->count() }}</span> pesanan</span>
                    <span class="text-border">·</span>
                    <span>💳 Total: <span class="font-bold text-primary">Rp {{ number_format($totalSpent, 0, ',', '.') }}</span></span>
                    @if($activeOrders > 0)
                        <span class="text-border">·</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">🟢 {{ $activeOrders }} aktif</span>
                    @endif
                </div>

                <!-- Results Header -->
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-foreground">
                        Daftar Pesanan
                        <span class="text-muted-foreground font-normal text-sm">({{ $orders->count() }} pesanan)</span>
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline-flex text-xs text-muted-foreground font-medium bg-muted px-3 py-1 rounded-full border border-border">NIK: {{ substr($nik, 0, 6) }}...</span>
                        <button type="button" wire:click="resetSearch" class="hidden sm:flex items-center gap-1.5 text-xs font-bold text-muted-foreground hover:text-primary transition-colors hover:bg-muted px-3 py-1 rounded-full border border-border">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
                            </svg>
                            Cari Ulang
                        </button>
                    </div>
                </div>

                @forelse ($orders as $order)
                    @php
                        $statusConfig = [
                            'pending'   => ['class' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-500', 'label' => 'Menunggu Bayar'],
                            'paid'      => ['class' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-500', 'label' => 'Sudah Dibayar'],
                            'completed' => ['class' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20', 'dot' => 'bg-blue-500', 'label' => 'Selesai'],
                            'cancelled' => ['class' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-500', 'label' => 'Dibatalkan'],
                        ];
                        $sc = $statusConfig[$order->status] ?? $statusConfig['pending'];
                    @endphp

                    @php
                        $isActiveRental = $order->status === 'paid' && $order->waktu_selesai->isFuture();
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
                        }"
                        x-init="{{ $isActiveRental ? 'tick(); setInterval(() => tick(), 1000)' : '' }}"
                        class="bg-card rounded-2xl border shadow-sm overflow-hidden {{ $order->status === 'pending' ? 'border-amber-400/50 dark:border-amber-500/30' : ($isActiveRental ? 'border-emerald-400/50 dark:border-emerald-500/30' : 'border-border') }}">

                        <!-- Row Header (Clickable) -->
                        <div @click="expanded = !expanded"
                            class="flex items-center gap-4 px-5 py-4 cursor-pointer hover:bg-muted/40 transition-colors group">

                            <!-- Icon -->
                            <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center text-muted-foreground shrink-0 group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                            </div>

                            <!-- Main Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-black text-foreground">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    @if($isActiveRental)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                            <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                            Berlangsung
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0 rounded-full text-[9px] font-bold border {{ $sc['class'] }}">
                                            <span class="w-1 h-1 rounded-full {{ $sc['dot'] }} {{ $order->status === 'pending' ? 'animate-pulse' : '' }}"></span>
                                            {{ $sc['label'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-[10px] text-muted-foreground">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
                                </div>
                            </div>

                            <!-- Total & Pay Button -->
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <div class="text-sm font-bold text-foreground">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</div>
                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                        {{ $order->waktu_mulai->format('d/m') }} &ndash; {{ $order->waktu_selesai->format('d/m') }}
                                    </p>
                                </div>

                                @if($order->status === 'pending')
                                    <a href="{{ route('public.payment', $order->id) }}" wire:navigate
                                        @click.stop
                                        class="hidden sm:flex items-center gap-1.5 h-9 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all shadow-sm shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>
                                        </svg>
                                        Bayar
                                    </a>
                                @endif
                            </div>

                            <!-- Chevron -->
                            <svg :class="expanded ? 'rotate-180' : ''"
                                xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="text-muted-foreground transition-transform duration-300 shrink-0">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>

                        <!-- Expanded Detail -->
                        <div x-show="expanded" x-collapse x-cloak>
                            <div class="px-5 pb-5 pt-1 border-t border-border space-y-5">

                                <!-- Units rented -->
                                <div class="space-y-2 pt-4">
                                    <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Item yang Disewa</p>
                                    <div class="space-y-2">
                                        @foreach($order->units as $unit)
                                            <div class="flex items-center gap-3 bg-muted/40 rounded-xl px-4 py-3">
                                                <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-foreground leading-tight">{{ $unit->seri }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ $unit->warna }} &bull; {{ $unit->memori }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Time & Cost -->
                                <div class="space-y-2">
                                    <div class="flex justify-between items-end mt-2">
                                        <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Waktu Sewa</p>
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
                                            <p class="text-sm font-semibold text-foreground">{{ $order->waktu_mulai->translatedFormat('d M Y') }}</p>
                                            <p class="text-xs text-muted-foreground">{{ $order->waktu_mulai->format('H:i') }}</p>
                                        </div>
                                        <div class="bg-muted/40 rounded-xl px-4 py-3">
                                            <p class="text-[10px] text-muted-foreground mb-0.5">Selesai</p>
                                            <p class="text-sm font-semibold text-foreground">{{ $order->waktu_selesai->translatedFormat('d M Y') }}</p>
                                            <p class="text-xs text-muted-foreground">{{ $order->waktu_selesai->format('H:i') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Breakdown -->
                                <div class="space-y-2">
                                    <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Rincian Biaya</p>
                                    <div class="bg-muted/40 rounded-xl px-4 py-3 space-y-2">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-muted-foreground">Subtotal</span>
                                            <span class="text-foreground">Rp {{ number_format($order->subtotal_harga, 0, ',', '.') }}</span>
                                        </div>

                                        @if($order->potongan_diskon > 0)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-muted-foreground">Diskon</span>
                                                <span class="text-rose-500">- Rp {{ number_format($order->potongan_diskon, 0, ',', '.') }}</span>
                                            </div>
                                        @endif

                                        @if($order->kode_unik_pembayaran)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-muted-foreground">Kode Unik</span>
                                                <span class="text-amber-500">+ {{ number_format($order->kode_unik_pembayaran, 0, ',', '.') }}</span>
                                            </div>
                                        @endif

                                        <div class="h-px bg-border"></div>

                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-foreground">Grand Total</span>
                                            <span class="text-base font-extrabold text-primary">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                @if($order->status == 'pending')
                                    <a href="{{ route('public.payment', $order->id) }}" wire:navigate
                                        class=" mt-4 flex items-center justify-center gap-2 w-full h-11 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>
                                        </svg>
                                        Lanjutkan Pembayaran
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-card rounded-2xl border border-dashed border-border p-10 text-center space-y-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="pt-4 mx-auto text-muted-foreground/40">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <p class="text-sm font-semibold text-foreground">Tidak ada pesanan ditemukan</p>
                        <p class="pb-4 text-xs text-muted-foreground">Pastikan NIK dan Nomor WA sudah sama persis dengan yang digunakan saat sewa.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>
