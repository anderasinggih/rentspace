{{-- Customer Session Banner (Active Rental, Pending Payment, or Account Shortcut) --}}
@if($isCustomerLoggedIn && $pendingOrders->count() > 0)
    {{-- Pending Payment Banner (Amber) --}}
    <div class="relative mb-8 rounded-xl border border-amber-500/30 bg-amber-500/5 text-card-foreground shadow-xs px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex items-center gap-3 shrink-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 shrink-0 border border-amber-500/20 text-amber-600 dark:text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" x2="12" y1="8" y2="12" />
                    <line x1="12" x2="12.01" y1="16" y2="16" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <p class="font-bold text-foreground text-sm">
                        {{ $onlinePendingTotal > 0 ? 'Pesanan Menunggu Pembayaran' : 'Pesanan Menunggu Pembayaran di Lokasi' }}
                    </p>
                    @if($customerTier)
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-tighter {{ $customerTier->color }} badge-shine shadow-xs shrink-0 mr-2">
                            {{ $customerTier->label }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Anda memiliki <span class="font-bold text-amber-600 dark:text-amber-400">{{ $pendingOrders->count() }} pesanan</span> {{ $onlinePendingTotal > 0 ? 'yang belum dibayar' : 'dengan metode bayar di tempat' }}.
                </p>
            </div>
        </div>
        <div class="flex sm:ml-auto w-full sm:w-auto mt-2 sm:mt-0">
            <a href="{{ route('public.check-order') }}" wire:navigate
                class="inline-flex flex-1 sm:flex-initial items-center justify-center rounded-lg bg-amber-500 text-white text-xs font-bold px-5 py-2.5 hover:bg-amber-600 transition-all shadow-xs shrink-0 whitespace-nowrap">
                {{ $onlinePendingTotal > 0 ? 'Bayar Sekarang' : 'Lihat Rincian' }}
            </a>
        </div>
    </div>
@elseif($isCustomerLoggedIn && $closestActiveRental)
    {{-- Active Rental Banner with Countdown (Green) --}}
    @php
        $selesaiTimestamp = $closestActiveRental->waktu_selesai->timestamp * 1000;
    @endphp
    <div x-data="{
            visible: false,
            countdown: '',
            status: 'green',
            endTime: {{ $selesaiTimestamp }},
            tick() {
                const now = Date.now();
                const diff = Math.floor((this.endTime - now) / 1000);
                if (diff <= 0) { 
                    this.countdown = 'Selesai'; 
                    this.status = 'red';
                    return; 
                }

                const hoursTotal = diff / 3600;
                if (hoursTotal < 3) {
                    this.status = 'red';
                } else if (hoursTotal < 6) {
                    this.status = 'amber';
                } else {
                    this.status = 'green';
                }

                const h = Math.floor(hoursTotal);
                const m = Math.floor((diff % 3600) / 60);
                const s = diff % 60;

                if(h > 0) {
                    this.countdown = h + 'j ' + m + 'm ' + s + 'd';
                } else {
                    this.countdown = m + 'm ' + s + 'd';
                }
            }
        }" x-init="tick(); setInterval(() => tick(), 1000)"
        class="relative mb-8 rounded-xl border border-border bg-card text-card-foreground shadow-xs px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex items-center gap-3.5 flex-1 min-w-0">
            <div class="relative flex h-10 w-10 items-center justify-center rounded-lg bg-muted border border-border shrink-0">
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border border-card"
                    :class="{ 'bg-emerald-500': status === 'green', 'bg-amber-500': status === 'amber', 'bg-red-500': status === 'red' }"></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="transition-colors duration-500"
                    :class="{ 'text-emerald-500': status === 'green', 'text-amber-500': status === 'amber', 'text-red-500': status === 'red' }">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
            </div>
            <div class="flex-1 min-w-0 flex justify-between sm:block sm:w-auto items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-foreground text-sm"
                            x-text="status === 'red' ? 'Masa Sewa Mau Habis' : 'Penyewaan Berlangsung'"></p>
                        @if($customerTier)
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-tighter {{ $customerTier->color }} badge-shine shadow-xs shrink-0 mr-2">
                                {{ $customerTier->label }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5 truncate pr-2 sm:pr-0">
                        KODE <span class="font-bold text-primary uppercase tracking-tighter">{{ $closestActiveRental->booking_code }}</span>
                    </p>
                </div>
                <div class="sm:hidden text-right">
                    <p class="text-[10px] font-bold uppercase transition-colors"
                        :class="{ 'text-emerald-500': status === 'green', 'text-amber-500': status === 'amber', 'text-red-600 animate-pulse': status === 'red' }"
                        x-text="status === 'red' ? 'SEGERA KEMBALIKAN' : 'Sisa Waktu'"></p>
                    <p x-text="countdown" class="font-black font-mono text-sm tracking-tight transition-colors"
                        :class="{ 'text-emerald-600 dark:text-emerald-400': status === 'green', 'text-amber-600 dark:text-amber-400': status === 'amber', 'text-red-600 dark:text-red-400': status === 'red' }">
                    </p>
                </div>
            </div>
        </div>

        <div class="hidden sm:block ml-auto mr-6 text-right relative z-10 w-48 shrink-0">
            <p class="text-[10px] font-bold uppercase tracking-widest mb-0.5 transition-colors"
                :class="{ 'text-muted-foreground': status !== 'red', 'text-red-500 animate-pulse': status === 'red' }"
                x-text="status === 'red' ? 'SEGERA KEMBALIKAN' : 'Sisa Waktu'"></p>
            <p x-text="countdown"
                class="font-black text-2xl sm:text-3xl font-mono tracking-tight transition-colors"
                :class="{ 'text-emerald-600 dark:text-emerald-400': status === 'green', 'text-amber-600 dark:text-amber-400': status === 'amber', 'text-red-600 dark:text-red-400': status === 'red' }">
            </p>
        </div>

        <div class="flex w-full sm:w-auto mt-2 sm:mt-0 relative z-10 sm:ml-0">
            <a href="{{ route('public.check-order') }}" wire:navigate
                class="inline-flex flex-1 sm:flex-initial items-center justify-center rounded-lg text-xs font-bold px-5 py-2.5 transition-all shadow-xs shrink-0 whitespace-nowrap bg-primary text-primary-foreground hover:bg-primary/90">
                Rincian Sewa
            </a>
        </div>
    </div>
@elseif($isCustomerLoggedIn)
    {{-- Logged In but No Active/Pending Banner (Blue) --}}
    <div class="relative mb-8 rounded-xl border border-primary/20 bg-primary/5 text-card-foreground shadow-xs px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex items-center gap-3 shrink-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 border border-primary/20 text-primary shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <p class="font-bold text-foreground text-sm">Sesi Peminjam Aktif</p>
                    @if($customerTier)
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-tighter {{ $customerTier->color }} badge-shine shadow-xs shrink-0 mr-2">
                            {{ $customerTier->label }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Akses pesanan lebih cepat karena Anda sudah masuk.
                </p>
            </div>
        </div>
        <div class="flex sm:ml-auto w-full sm:w-auto mt-2 sm:mt-0">
            <a href="{{ route('public.check-order') }}" wire:navigate
                class="inline-flex flex-1 sm:flex-initial items-center justify-center rounded-lg bg-primary text-primary-foreground text-xs font-bold px-5 py-2.5 transition-all shadow-xs shrink-0 whitespace-nowrap hover:bg-primary/90">
                Cek Riwayat
            </a>
        </div>
    </div>
@elseif(!$isCustomerLoggedIn)
    {{-- Login CTA for guests --}}
    <div x-data="{ visible: false }" x-intersect.once="visible = true"
        :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
        class="relative mb-8 rounded-xl border border-border bg-card text-card-foreground px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all duration-300 shadow-sm">
        
        <div class="flex items-center gap-3.5 flex-1 min-w-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 border border-primary/20 text-primary shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </div>
            <div>
                <p class="font-bold text-foreground text-sm">Sudah pernah booking sebelumnya?</p>
                <p class="text-xs text-muted-foreground mt-0.5">Masuk untuk melihat riwayat pesanan, status sewa aktif, dan invoice Anda.</p>
            </div>
        </div>
        <a href="{{ route('customer.login') }}" wire:navigate
            data-haptic="medium"
            class="inline-flex items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold px-6 py-2 hover:bg-primary/90 transition-all shadow-xs w-full sm:w-auto shrink-0">
            Masuk
        </a>
    </div>
@endif
