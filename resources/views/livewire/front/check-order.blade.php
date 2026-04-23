<div class="py-2 px-4 sm:px-6 lg:px-8 bg-background min-h-[calc(100vh-4rem)]">
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
                                    'pending' => ['class' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-500', 'label' => 'Menunggu Bayar'],
                                    'paid' => ['class' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-500', 'label' => 'Sudah Dibayar'],
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
                                    }" x-init="{{ $isActiveRental ? 'tick(); setInterval(() => tick(), 1000)' : '' }}"
                                class="bg-card rounded-2xl border shadow-sm overflow-hidden {{ $order->status === 'pending' ? 'border-amber-400/50 dark:border-amber-500/30' : ($isActiveRental ? 'border-emerald-400/50 dark:border-emerald-500/30' : 'border-border') }}">

                                <!-- Row Header (Clickable) -->
                                <div @click="expanded = !expanded"
                                    class="flex items-center gap-3 px-5 py-4 cursor-pointer hover:bg-muted/40 transition-colors group">


                                    <!-- Main Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="text-xs font-black text-foreground">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            @if($isActiveRental)
                                                <span
                                                    class="inline-flex items-center gap-1 px-1.5 py-0 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                                    Berlangsung
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
                                                                {{ $unit->seri }}</p>
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
                    @php $firstOrder = $orders ? $orders->first() : null; @endphp

                    {{-- Simple Shadcn-style Profile Card --}}
                    <div class="bg-card border border-border rounded-3xl overflow-hidden shadow-sm">
                        <div class="bg-muted/30 px-6 py-5 border-b border-border flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-lg text-foreground">{{ $firstOrder?->nama ?? 'Akun Peminjam' }}
                                </h3>
                                <p class="text-xs text-muted-foreground font-medium">Sesi Identitas Aktif Tersimpan</p>
                            </div>
                            <div
                                class="h-10 w-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
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