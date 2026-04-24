<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" x-bind:class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'RENT SPACE') }} PURWOKERTO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased min-h-screen bg-background text-foreground flex flex-col">
    <livewire:front.global-announcement />

    <!-- Navbar Publik Baru yang identik dengan Admin Navbar -->
    <livewire:navbar />

    <main class="flex-1 w-full">
        @php
            \Carbon\Carbon::setLocale('id');
            $customerSession = session('customer_session');
            $isCustomerLoggedIn = $customerSession
                && isset($customerSession['expires_at'])
                && now()->timestamp < $customerSession['expires_at'];

            $pendingOrders = collect();
            $closestActiveRental = null;

            if ($isCustomerLoggedIn) {
                $pendingOrders = \App\Models\Rental::with('units')
                    ->where('nik', $customerSession['nik'])
                    ->where('no_wa', $customerSession['no_wa'])
                    ->where('status', 'pending')
                    ->latest()
                    ->get();

                $onlinePendingTotal = $pendingOrders->where('metode_pembayaran', '!=', 'cash')->count();
                $cashPendingTotal = $pendingOrders->where('metode_pembayaran', 'cash')->count();

                $closestActiveRental = \App\Models\Rental::where('nik', $customerSession['nik'])
                    ->where('no_wa', $customerSession['no_wa'])
                    ->where('status', 'paid')
                    ->where('waktu_selesai', '>', now())
                    ->orderBy('waktu_selesai', 'asc')
                    ->first();
            }

            $statsTotalRentals = \App\Models\Rental::count();
            $statsTotalUsers = \App\Models\Rental::distinct('nik')->count('nik');
            $statsTotalHours = round(\App\Models\Rental::whereNotNull('waktu_mulai')->whereNotNull('waktu_selesai')->get()->sum(function ($r) {
                return \Carbon\Carbon::parse($r->waktu_mulai)->diffInHours(\Carbon\Carbon::parse($r->waktu_selesai));
            }));

            $statsTotalRentals = $statsTotalRentals > 0 ? $statsTotalRentals : 1;
            $statsTotalUsers = $statsTotalUsers > 0 ? $statsTotalUsers : 1;
            $statsTotalHours = $statsTotalHours > 0 ? $statsTotalHours : 24;

            // Social Proof Ticker Data
            $recentRentals = \App\Models\Rental::with('units')
                ->whereIn('status', ['paid', 'pending', 'cancelled'])
                ->latest()
                ->take(15)
                ->get()
                ->map(function($r) {
                    $name = $r->nama ?: 'Pelanggan';
                    $parts = explode(' ', trim($name));
                    $firstName = $parts[0];
                    $censoredName = strlen($firstName) > 2 
                        ? substr($firstName, 0, 1) . str_repeat('*', min(strlen($firstName)-2, 3)) . substr($firstName, -1)
                        : $firstName . '***';
                    
                    $unitName = $r->units->first() ? $r->units->first()->seri : 'iPhone';
                    $action = 'baru sewa';
                    if($r->status === 'pending') $action = 'sedang booking';
                    if($r->status === 'cancelled') $action = 'baru batal';
                    
                    return [
                        'name' => $censoredName,
                        'unit' => $unitName,
                        'time' => $r->created_at->diffForHumans(),
                        'action' => $action,
                        'status' => $r->status,
                    ];
                });
        @endphp

        <!-- Hero section -->
        <section x-data="{ 
                activeSlide: 0,
                spotlight: false,
                slides: [
                    '/uploads/{{ \App\Models\Setting::getVal('hero', 'default.jpg') }}?t={{ time() }}',
                    '/uploads/{{ \App\Models\Setting::getVal('hero2', 'default2.jpg') }}?t={{ time() }}',
                    '/uploads/{{ \App\Models\Setting::getVal('hero3', 'default3.jpg') }}?t={{ time() }}'
                ],
                init() {
                    setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                    }, 6000);
                }
            }"
            class="relative w-full overflow-hidden flex flex-col items-center text-center py-24 sm:py-36 mb-8 sm:rounded-[2rem] sm:mx-6 lg:max-w-7xl lg:mx-auto mt-0 sm:mt-6 shadow-2xl">
            
            <!-- Background Image Slideshow -->
            <div class="absolute inset-0 z-0 bg-white dark:bg-zinc-950 overflow-hidden text-zinc-950 dark:text-white">
                @for($i = 0; $i < 3; $i++)
                    @php
                        $key = $i == 0 ? 'hero' : 'hero' . ($i + 1);
                        $image = \App\Models\Setting::getVal($key, 'default.jpg');
                    @endphp
                    <div 
                         class="absolute inset-0 w-full h-full transition-opacity duration-[2000ms] ease-in-out"
                         :class="activeSlide === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    >
                        <img src="/uploads/{{ $image }}" 
                             class="w-full h-full object-cover opacity-60"
                             onerror="this.style.opacity='0.1'">
                    </div>
                @endfor
                
                <div class="absolute inset-0 bg-gradient-to-t from-white via-white/50 to-transparent dark:from-zinc-950 dark:via-zinc-950/50 z-20"></div>
                <div class="absolute inset-0 bg-white/40 dark:bg-black/40 z-20 transition-all duration-700"
                    :class="spotlight ? 'bg-black/80 backdrop-blur-[2px]' : ''"></div>

            </div>

            <!-- Teks -->
            <div class="relative z-30 w-full flex flex-col items-center text-center px-4 sm:px-6 lg:px-8 cursor-default">
                @if($isCustomerLoggedIn && $closestActiveRental)
                    @php
                        $selesaiHeroTimestamp = $closestActiveRental->waktu_selesai->timestamp * 1000;
                    @endphp
                    <div x-data="{
                                    countdown: '',
                                    status: 'green',
                                    endTime: {{ $selesaiHeroTimestamp }},
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
                        class="mb-8 flex flex-col items-center animate-in fade-in slide-in-from-bottom-4 duration-700">
                        <span
                            class="text-[10px] sm:text-xs font-bold uppercase tracking-widest mb-1.5 flex items-center gap-1.5 backdrop-blur-sm px-3 py-1 rounded-full border transition-colors duration-500"
                            :class="{
                                          'bg-emerald-500/20 border-emerald-500/30 text-emerald-300': status === 'green',
                                          'bg-amber-500/20 border-amber-500/30 text-amber-300': status === 'amber',
                                          'bg-red-500/20 border-red-500/40 text-red-200': status === 'red'
                                      }">
                            <span
                                class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full animate-pulse transition-colors duration-500"
                                :class="{
                                              'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]': status === 'green',
                                              'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]': status === 'amber',
                                              'bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.9)]': status === 'red'
                                          }"></span>
                            <span
                                x-text="status === 'red' ? 'HAMPIR HABIS - SEGERA KEMBALIKAN!' : 'Sisa Waktu Pengembalian'"></span>
                        </span>
                        <div x-text="countdown"
                            class="text-5xl sm:text-7xl lg:text-8xl font-black font-mono tracking-tighter transition-all duration-500"
                            :class="{
                                          'text-emerald-400 drop-shadow-[0_0_20px_rgba(52,211,153,0.5)]': status === 'green',
                                          'text-amber-400 drop-shadow-[0_0_25px_rgba(251,191,36,0.6)]': status === 'amber',
                                          'text-red-500 drop-shadow-[0_0_30px_rgba(248,113,113,0.8)] animate-pulse': status === 'red'
                                     }"></div>
                    </div>
                @endif
                <div
                    class="inline-flex items-center rounded-full border-t border-l border-white/40 bg-white/10 dark:bg-white/5 backdrop-blur-md text-white px-4 py-1.5 text-xs font-semibold mb-8 cursor-default tracking-widest uppercase transition-all duration-700 shadow-[inset_0_1px_1px_rgba(255,255,255,0.3)]"
                    :class="spotlight ? 'opacity-30 translate-y-2' : ''">
                    <span class="relative z-10">RENT SPACE PURWOKERTO</span>
                </div>
                <h1 @mouseenter="spotlight = true" @mouseleave="spotlight = false"
                    class="text-3xl font-extrabold tracking-tight sm:text-5xl xl:text-6xl text-zinc-950 dark:text-white uppercase max-w-4xl transition-all duration-700 cursor-pointer relative z-40"
                    :class="spotlight ? 'drop-shadow-[0_0_60px_rgba(255,255,255,0.7)] scale-[1.02]' : ''">
                    {!! nl2br(e(\App\Models\Setting::getVal('home_title', "Sewa iPhone Impian Anda Lebih Mudah & Cepat"))) !!}
                </h1>
                <p class="mt-6 text-base sm:text-xl leading-relaxed sm:leading-8 text-zinc-600 dark:text-zinc-300 font-medium max-w-2xl transition-all duration-700"
                    :class="spotlight ? 'opacity-40 scale-95 translate-y-[-10px]' : ''">
                    {{ \App\Models\Setting::getVal('home_description', 'Pilihan terbaik untuk merasakan pengalaman
                    menggunakan produk Apple original tanpa harus membeli baru. Aman, transparan dan terpercaya.') }}
                </p>
                <div
                    class="mt-12 grid grid-cols-2 sm:flex sm:flex-row items-center gap-2 sm:gap-4 w-full px-2 sm:px-0 justify-center transition-all duration-700"
                    :class="spotlight ? 'opacity-20 translate-y-4' : ''">
                    
                    <!-- Button SEWA SEKARANG (Liquid Glass) -->
                    <a href="{{ route('public.booking') }}" wire:navigate
                        class="group/btn relative w-full sm:w-auto inline-flex items-center justify-center rounded-xl font-bold transition-all bg-white/15 dark:bg-white/10 backdrop-blur-md backdrop-saturate-[180%] backdrop-contrast-[110%] border-t border-l border-white/50 border-r border-b border-white/20 text-foreground shadow-[0_8px_32px_rgba(255,255,255,0.1)] hover:scale-[1.05] active:scale-95 min-w-0 sm:min-w-[200px] h-12 px-2 sm:px-8 py-2 text-xs sm:text-base whitespace-nowrap overflow-hidden z-30">
                        <!-- Liquid Shine Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent pointer-events-none transition-opacity duration-300 group-hover/btn:opacity-100 opacity-60"></div>
                        <span class="relative z-10 tracking-wider">SEWA SEKARANG</span>
                    </a>

                    <!-- Button Hubungi Admin (Liquid Glass) -->
                    <a href="https://wa.me/{{ \App\Models\Setting::getVal('admin_wa', '6281234567890') }}"
                        target="_blank" rel="noopener"
                        class="group/wa relative w-full sm:w-auto inline-flex items-center justify-center rounded-xl font-bold transition-all bg-zinc-950/30 dark:bg-white/10 backdrop-blur-md backdrop-saturate-[180%] backdrop-contrast-[110%] border-t border-l border-white/30 border-r border-b border-zinc-950/10 text-foreground hover:scale-[1.05] active:scale-95 min-w-0 sm:min-w-[200px] h-12 px-2 sm:px-8 py-2 text-xs sm:text-base whitespace-nowrap overflow-hidden z-30">
                        <!-- Liquid Shine Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-transparent pointer-events-none transition-opacity duration-300 group-hover/wa:opacity-100 opacity-40"></div>
                        <span class="relative z-10 flex items-center justify-center tracking-wider">
                            @php
                                $whatsappIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                                                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                                                                                                                                stroke-linecap="round" stroke-linejoin="round" class="mr-1 sm:mr-2 shrink-0">
                                                                                                                                                <path
                                                                                                                                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                                                                                                                            </svg>';
                        @endphp
                        {!! $whatsappIcon !!}
                        HUBUNGI ADMIN
                    </a>
                </div>

                <!-- Hero Placement Announcement -->
                <livewire:front.global-announcement placement="hero" />
            </div>
        </section>

        <!-- Public Stats Widget -->
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 -mt-16 sm:-mt-20 mb-10">
            <div class="grid grid-cols-3 divide-x divide-white/5 bg-white/[0.005] dark:bg-white/[0.002] backdrop-blur-[4px] border-t border-l border-white/20 shadow-2xl rounded-2xl sm:rounded-3xl overflow-hidden py-0 relative group/stats">
                <!-- Specular Reflection Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-transparent pointer-events-none z-20"></div>
                <!-- Total Transaksi -->
                <div class="group relative flex flex-col items-center text-center px-1 sm:px-4 py-3 sm:py-5 transition-all duration-300 hover:bg-white/5 hover:z-30 hover:shadow-[0_0_40px_rgba(255,255,255,0.1)]" 
                     x-data="{ 
                         target: {{ $statsTotalRentals }}, 
                         display: '0', 
                         format(val) {
                             if (val >= 1000) return (val/1000).toFixed(1).replace('.0', '') + 'K';
                             return Math.floor(val);
                         },
                         run() { 
                             let start = null;
                             const duration = 2000;
                             const animate = (timestamp) => {
                                 if (!start) start = timestamp;
                                 const progress = timestamp - start;
                                 const easeOut = 1 - Math.pow(1 - Math.min(progress / duration, 1), 3);
                                 this.display = this.format(easeOut * this.target);
                                 if (progress < duration) requestAnimationFrame(animate);
                             };
                             requestAnimationFrame(animate);
                         } 
                     }" x-intersect.once="run()">
                    <!-- White Glow Blob -->
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-xl group-hover:bg-white/10 transition-all duration-700 z-0"></div>
                    
                    <span class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-widest mb-1 sm:mb-2 group-hover:text-foreground transition-colors duration-500">Transaksi</span>
                    <span class="text-xl sm:text-4xl font-black text-foreground group-hover:scale-110 transition-all duration-500"><span x-text="display"></span><span class="text-white text-base sm:text-2xl ml-0.5">+</span></span>
                </div>

                <!-- Pelanggan -->
                <div class="group relative flex flex-col items-center text-center px-1 sm:px-4 py-3 sm:py-5 transition-all duration-300 hover:bg-white/5 hover:z-30 hover:shadow-[0_0_40px_rgba(255,255,255,0.1)]" 
                     x-data="{ 
                         target: {{ $statsTotalUsers }}, 
                         display: '0', 
                         format(val) {
                             if (val >= 1000) return (val/1000).toFixed(1).replace('.0', '') + 'K';
                             return Math.floor(val);
                         },
                         run() { 
                             let start = null;
                             const duration = 2200;
                             const animate = (timestamp) => {
                                 if (!start) start = timestamp;
                                 const progress = timestamp - start;
                                 const easeOut = 1 - Math.pow(1 - Math.min(progress / duration, 1), 3);
                                 this.display = this.format(easeOut * this.target);
                                 if (progress < duration) requestAnimationFrame(animate);
                             };
                             requestAnimationFrame(animate);
                         } 
                     }" x-intersect.once="run()">
                    <!-- White Glow Blob -->
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-xl group-hover:bg-white/10 transition-all duration-700 z-0"></div>

                    <span class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-widest mb-1 sm:mb-2 group-hover:text-foreground transition-colors duration-500">Pelanggan</span>
                    <span class="text-xl sm:text-4xl font-black text-foreground group-hover:scale-110 transition-all duration-500"><span x-text="display"></span><span class="text-white text-base sm:text-2xl ml-0.5">+</span></span>
                </div>

                <!-- Jam Disewa -->
                <div class="group relative flex flex-col items-center text-center px-1 sm:px-4 py-3 sm:py-5 transition-all duration-300 hover:bg-white/5 hover:z-30 hover:shadow-[0_0_40px_rgba(255,255,255,0.1)]" 
                     x-data="{ 
                         target: {{ $statsTotalHours }}, 
                         display: '0', 
                         format(val) {
                             if (val >= 1000) return (val/1000).toFixed(1).replace('.0', '') + 'K';
                             return Math.floor(val);
                         },
                         run() { 
                             let start = null;
                             const duration = 2500;
                             const animate = (timestamp) => {
                                 if (!start) start = timestamp;
                                 const progress = timestamp - start;
                                 const easeOut = 1 - Math.pow(1 - Math.min(progress / duration, 1), 3);
                                 this.display = this.format(easeOut * this.target);
                                 if (progress < duration) requestAnimationFrame(animate);
                             };
                             requestAnimationFrame(animate);
                         } 
                     }" x-intersect.once="run()">
                    <!-- White Glow Blob -->
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-xl group-hover:bg-white/10 transition-all duration-700 z-0"></div>

                    <span class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-widest mb-1 sm:mb-2 group-hover:text-white transition-colors duration-300">Jam Disewa</span>
                    <span class="text-xl sm:text-4xl font-black text-foreground group-hover:scale-110 transition-all duration-300"><span x-text="display"></span><span class="text-white text-base sm:text-2xl ml-0.5">+</span></span>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">

            {{-- Customer Session Banner --}}

            @if($isCustomerLoggedIn && $pendingOrders->count() > 0)
                {{-- Pending Payment Banner (Amber) --}}
                <div x-data="{ visible: false }" x-intersect.once="visible = true"
                    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
                    class="group relative mb-8 rounded-2xl border-t border-l border-amber-500/20 bg-amber-500/[0.005] dark:bg-amber-500/[0.002] backdrop-blur-[4px] shadow-xl px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all duration-500 hover:border-amber-500/60 hover:shadow-2xl hover:shadow-amber-500/20 overflow-hidden">
                    
                    <!-- Liquid Glow Blob -->
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 group-hover:scale-150 transition-all duration-1000 z-0"></div>
                    
                    <!-- Specular Highlight -->
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 via-transparent to-transparent pointer-events-none z-10"></div>
                    <div class="flex items-center gap-3 shrink-0">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/10 shrink-0 border border-amber-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="text-amber-500">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" x2="12" y1="8" y2="12" />
                                <line x1="12" x2="12.01" y1="16" y2="16" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-foreground text-sm">
                                {{ $onlinePendingTotal > 0 ? 'Pesanan Menunggu Pembayaran' : 'Pesanan Menunggu Pembayaran di Lokasi' }}
                            </p>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Anda memiliki <span class="font-bold text-amber-500">{{ $pendingOrders->count() }}
                                    pesanan</span> {{ $onlinePendingTotal > 0 ? 'yang belum dibayar' : 'dengan metode bayar di tempat' }}.
                            </p>
                        </div>
                    </div>
                    <div class="flex sm:ml-auto w-full sm:w-auto mt-2 sm:mt-0 relative z-10">
                        <a href="{{ route('public.check-order') }}" wire:navigate
                            class="inline-flex flex-1 sm:flex-initial items-center justify-center rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-bold px-5 py-2.5 hover:bg-amber-500 hover:text-white transition-all duration-300 shadow-sm shrink-0 whitespace-nowrap group-hover:scale-105 group-hover:shadow-lg group-hover:shadow-amber-500/20">
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
                            }" x-init="tick(); setInterval(() => tick(), 1000)" x-intersect.once="visible = true" :class="[
                            visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16',
                            status === 'red' ? 'border-red-500/30 bg-red-500/5 hover:border-red-500/60 hover:shadow-red-500/20' : '',
                            status === 'green' ? 'border-emerald-500/20 bg-emerald-500/[0.01] hover:border-emerald-500/50 hover:shadow-emerald-500/10' : '',
                            status === 'amber' ? 'border-amber-500/20 bg-amber-500/[0.01] hover:border-amber-500/50 hover:shadow-amber-500/10' : ''
                        ]"
                    class="group relative mb-8 rounded-2xl border backdrop-blur-md shadow-sm px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 overflow-hidden transition-all duration-300">
                    
                    <!-- Dynamic Glow Blob -->
                    <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full blur-2xl transition-all duration-500 z-0"
                         :class="{
                             'bg-emerald-500/5 group-hover:bg-emerald-500/15': status === 'green',
                             'bg-amber-500/5 group-hover:bg-amber-500/15': status === 'amber',
                             'bg-red-500/10 group-hover:bg-red-500/20': status === 'red'
                         }"></div>
                    <div class="flex items-center gap-3 shrink-0 relative z-10 w-full sm:w-auto">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full shrink-0 relative border transition-colors duration-500"
                            :class="{
                                         'bg-emerald-500/10 border-emerald-500/20': status === 'green',
                                         'bg-amber-500/10 border-amber-500/20': status === 'amber',
                                         'bg-red-500/10 border-red-500/30': status === 'red'
                                     }">
                            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full animate-ping"
                                :class="{ 'bg-emerald-500': status === 'green', 'bg-amber-500': status === 'amber', 'bg-red-500': status === 'red' }"></span>
                            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border"
                                :class="{ 'bg-emerald-500 border-emerald-200': status === 'green', 'bg-amber-500 border-amber-200': status === 'amber', 'bg-red-500 border-red-200': status === 'red' }"></span>
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
                                <p class="font-bold text-foreground text-sm flex items-center gap-2"
                                    x-text="status === 'red' ? 'Masa Sewa Mau Habis' : 'Penyewaan Berlangsung'"></p>
                                <p class="text-xs text-muted-foreground mt-0.5 truncate pr-2 sm:pr-0">
                                    KODE <span
                                        class="font-bold text-primary uppercase tracking-tighter">{{ $closestActiveRental->booking_code }}</span>
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
                            class="inline-flex flex-1 sm:flex-initial items-center justify-center rounded-xl text-xs font-bold px-5 py-2.5 transition-all duration-300 shadow-sm shrink-0 whitespace-nowrap group-hover:scale-105 group-hover:shadow-lg"
                            :class="{
                                        'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:text-white shadow-emerald-500/10': status === 'green',
                                        'bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-white shadow-amber-500/10': status === 'amber',
                                        'bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-500 hover:text-white shadow-red-500/10': status === 'red'
                                    }">
                            Rincian Sewa
                        </a>
                    </div>
                </div>
            @elseif($isCustomerLoggedIn)
                {{-- Logged In but No Active/Pending Banner (Blue) --}}
                <div x-data="{ visible: false }" x-intersect.once="visible = true"
                    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
                    class="group relative mb-8 rounded-2xl border-t border-l border-blue-500/20 bg-blue-500/[0.005] dark:bg-blue-500/[0.002] backdrop-blur-[4px] shadow-xl px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all duration-500 hover:border-blue-500/60 hover:shadow-2xl hover:shadow-blue-500/20 overflow-hidden">
                    
                    <!-- Liquid Glow Blob -->
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 group-hover:scale-150 transition-all duration-1000 z-0"></div>

                    <!-- Specular Highlight -->
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 via-transparent to-transparent pointer-events-none z-10"></div>
                    <div class="flex items-center gap-3 shrink-0">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500/10 border border-blue-500/20 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="text-blue-500">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-foreground text-sm">Sesi Peminjam Aktif</p>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Akses pesanan lebih cepat karena Anda sudah masuk.
                            </p>
                        </div>
                    </div>
                    <div class="flex sm:ml-auto w-full sm:w-auto mt-2 sm:mt-0 relative z-10">
                        <a href="{{ route('public.check-order') }}" wire:navigate
                            class="inline-flex flex-1 sm:flex-initial items-center justify-center rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-600 dark:text-blue-400 text-xs font-bold px-5 py-2.5 transition-all duration-300 shadow-sm shadow-blue-500/10 shrink-0 whitespace-nowrap hover:bg-blue-600 hover:text-white hover:shadow-lg hover:shadow-blue-500/20 group-hover:scale-105">
                            Cek Riwayat
                        </a>
                    </div>
                </div>
            @elseif(!$isCustomerLoggedIn)
                {{-- Login CTA for guests --}}
                <div x-data="{ visible: false }" x-intersect.once="visible = true"
                    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
                    class="mb-8 rounded-2xl border border-border bg-card/50 px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-3 transition-all duration-1000 ease-out">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="text-primary">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-foreground text-sm">Sudah pernah booking?</p>
                            <p class="text-xs text-muted-foreground mt-0.5">Masuk untuk melihat status pesanan dan
                                notifikasi pembayaran Anda.</p>
                        </div>
                    </div>
                    <a href="{{ route('customer.login') }}" wire:navigate
                        class="inline-flex items-center justify-center rounded-xl bg-primary text-primary-foreground text-xs font-semibold px-4 py-2.5 hover:bg-primary/90 transition-colors shadow-sm w-full sm:w-auto ">
                        Masuk
                    </a>
                </div>
            @endif
            {{-- End Customer Session Banner --}}

            <div x-data="{ visible: false }" x-intersect.once="visible = true"
                :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
                class="text-center mb-8 transition-all duration-1000 ease-out">
                <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Promo Spesial Aktif</h2>
                <p class="mt-4 text-sm sm:text-base text-muted-foreground">Promo yang tersedia pada saat ini.</p>
            </div>
            <!-- Promo -->
            @php
                $now = \Carbon\Carbon::now();
                $promos = \App\Models\PricingRule::where('is_active', true)
                    ->where('is_hidden', false)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_date')->orWhere('start_date', '<=', $now->format('Y-m-d'));
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $now->format('Y-m-d'));
                    })
                    ->get();
            @endphp
            @if($promos->count() > 0)
                <div x-data="{ expandedPromo: false, visible: false }" x-intersect.once="visible = true"
                    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'"
                    class="mt-8 w-full transition-all duration-1000 delay-100 ease-out">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        @foreach($promos as $promo)
                            <a href="{{ route('public.booking') }}" wire:navigate
                                :class="{ 
                                    'hidden': !expandedPromo && {{ $loop->index }} >= 2, 
                                    'sm:block': !expandedPromo && {{ $loop->index }} >= 2 && {{ $loop->index }} < 4,
                                    'sm:hidden': !expandedPromo && {{ $loop->index }} >= 4,
                                    'block': expandedPromo || {{ $loop->index }} < 2 
                                }"
                                class="p-4 sm:p-6 bg-emerald-500/[0.005] dark:bg-emerald-500/[0.002] backdrop-blur-[4px] shadow-xl border-t border-l border-emerald-500/30 rounded-2xl hover:border-emerald-500/60 hover:shadow-2xl hover:shadow-emerald-500/20 transition-all duration-500 ease-out group relative overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-700 delay-[{{ $loop->index * 100 }}ms]">
                                
                                <!-- Specular Highlight -->
                                <div class="absolute inset-0 bg-gradient-to-br from-white/5 via-transparent to-transparent pointer-events-none z-10"></div>

                                <!-- Liquid Glow Blobs (Moving) -->
                                <div class="absolute -right-12 -top-12 w-40 h-40 bg-emerald-400/15 rounded-full blur-3xl animate-pulse group-hover:bg-emerald-400/25 group-hover:scale-125 transition-all duration-1000 ease-in-out z-0"></div>
                                <div class="absolute -left-12 -bottom-12 w-40 h-40 bg-green-500/10 rounded-full blur-3xl animate-bounce [animation-duration:8s] group-hover:bg-green-500/20 transition-all duration-1000 ease-in-out z-0"></div>

                                <div class="relative z-10 flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-3 gap-3">
                                            <h3 class="font-bold text-foreground text-sm sm:text-base truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-300">
                                                {{ $promo->nama_promo }}</h3>
                                            <x-ui.badge variant="green"
                                                class="uppercase tracking-tight text-[9px] sm:text-[10px] px-1.5 sm:px-2 py-0.5 shrink-0 animate-pulse">Promo
                                                Aktif</x-ui.badge>
                                        </div>

                                        @php
                                            $durasi = $promo->syarat_minimal_durasi . ' ' . ucfirst($promo->syarat_tipe_durasi);
                                            $promoText = '';
                                            if ($promo->tipe === 'diskon_persen')
                                                $promoText = "Diskon " . $promo->value . "%";
                                            elseif ($promo->tipe === 'diskon_nominal')
                                                $promoText = "Potongan Rp " .
                                                    number_format($promo->value, 0, ',', '.');
                                            elseif ($promo->tipe === 'fix_price')
                                                $promoText = "Harga Spesial Rp " .
                                                    number_format($promo->value, 0, ',', '.');
                                            elseif ($promo->tipe === 'hari_gratis')
                                                $promoText = "Gratis " . $promo->value . " Hari";
                                            elseif ($promo->tipe === 'jam_gratis')
                                                $promoText = "Gratis " . $promo->value . " Jam";
                                            elseif ($promo->tipe === 'cashback')
                                                $promoText = "Cashback Rp " .
                                                    number_format($promo->value, 0, ',', '.');
                                        @endphp

                                        <div class="flex items-center justify-between gap-4 mb-1">
                                            <p class="text-[10px] sm:text-xs text-muted-foreground truncate">Min. sewa {{ $durasi }}</p>
                                            @if($promo->end_date)
                                                <div x-data="{
                                                                timeLeft: '',
                                                                endTime: new Date('{{ $promo->end_date }} 23:59:59').getTime(),
                                                                update() {
                                                                    const now = new Date().getTime();
                                                                    const diff = this.endTime - now;
                                                                    if (diff <= 0) { this.timeLeft = 'SELESAI'; return; }
                                                                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                                                                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                                                    const s = Math.floor((diff % (1000 * 60)) / 1000);
                                                                    if (d > 0) {
                                                                        this.timeLeft = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
                                                                    } else {
                                                                        this.timeLeft = h + 'h ' + m + 'm ' + s + 's';
                                                                    }
                                                                }
                                                            }" x-init="update(); setInterval(() => update(), 1000)"
                                                    class="bg-zinc-900 dark:bg-zinc-800 text-white px-1.5 sm:px-2 py-0.5 rounded-md text-[9px] sm:text-[10px] font-mono font-bold tracking-tighter shrink-0"
                                                    x-text="timeLeft"></div>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between gap-4 mt-2">
                                            <span class="font-semibold text-primary/90 text-xs sm:text-sm block truncate tracking-tight">{{ $promoText }}</span>
                                            @if($promo->start_date || $promo->end_date)
                                                <div class="text-[8px] sm:text-[9px] text-muted-foreground font-medium shrink-0 leading-none">
                                                    {{ $promo->start_date ? \Carbon\Carbon::parse($promo->start_date)->format('d M') : 'Sekarang' }}
                                                    -
                                                    {{ $promo->end_date ? \Carbon\Carbon::parse($promo->end_date)->format('d M y') : 'Selesai' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
 
                    @if($promos->count() > 2)
                        <div class="mt-8 flex justify-center {{ $promos->count() <= 4 ? 'sm:hidden' : '' }}">
                            <button @click="expandedPromo = !expandedPromo"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full border border-border bg-card text-[10px] font-bold text-muted-foreground hover:text-foreground hover:border-primary/50 transition-all shadow-sm group/btn">
                                <span x-text="expandedPromo ? 'Sembunyikan Promo' : 'Lihat Promo Lainnya'"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                    :class="expandedPromo ? 'rotate-180' : ''" class="transition-transform duration-300">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Testimonials Marquee -->
            @php
                $realFeedbacks = collect();
                if (\Illuminate\Support\Facades\Schema::hasColumn('rentals', 'rating')) {
                    $realFeedbacks = \App\Models\Rental::whereNotNull('rating')
                        ->where('rating', '>=', 4)
                        ->whereNotNull('feedback')
                        ->where('feedback', '!=', '')
                        ->latest()
                        ->take(10)
                        ->get();
                }
            @endphp

            <!-- Testimonials Marquee Section -->
            @if($realFeedbacks->count() > 0)
            <div x-data="{ visible: false }" x-intersect.once="visible = true"
                :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
                class="mt-12 transition-all duration-1000 ease-out">
                <div class="text-center mb-12">
                    <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Kata Mereka</h2>
                    <p class="mt-2 text-sm sm:text-base text-muted-foreground">Ribuan pengalaman manis bersama Rent Space</p>
                </div>
 
                <div class="relative overflow-hidden py-4">
                    <!-- Infinite Marquee Row 1 -->
                    <div class="flex flex-nowrap gap-6 animate-marquee whitespace-nowrap mb-6">
                        @php
                            $loopCount = $realFeedbacks->count() <= 2 ? 6 : ($realFeedbacks->count() <= 5 ? 3 : 2);
                            $finalFeedbacks = [];
                            for($i=0; $i<$loopCount; $i++) { 
                                foreach($realFeedbacks as $fb) { $finalFeedbacks[] = $fb; }
                            }
                        @endphp
                        @foreach($finalFeedbacks as $item)
                            <div class="group relative inline-block w-[260px] sm:w-[320px] bg-violet-500/[0.005] dark:bg-violet-500/[0.002] backdrop-blur-[4px] border-t border-l border-violet-500/30 p-4 sm:p-6 rounded-2xl sm:rounded-[2rem] transition-all duration-500 hover:border-violet-500/60 hover:shadow-2xl hover:shadow-violet-500/20 overflow-hidden">
                                <!-- Specular Highlight -->
                                <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-transparent pointer-events-none z-10"></div>
                                
                                <!-- Violet Liquid Glow Blob -->
                                <div class="absolute -right-8 -top-8 w-24 h-24 bg-violet-500/15 rounded-full blur-3xl group-hover:bg-violet-500/25 group-hover:scale-150 transition-all duration-1000 z-0"></div>
                                
                                <div class="relative z-10 flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-violet-500/20 border border-violet-500/20 flex items-center justify-center text-[10px] sm:text-[12px] font-bold text-violet-600 dark:text-violet-400">
                                        {{ substr($item->nama, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-[11px] sm:text-xs font-bold text-foreground leading-tight group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $item->nama }}</p>
                                        <div class="flex gap-0.5">
                                            @for($s=1; $s<=5; $s++)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="{{ $item->rating >= $s ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2.5" class="{{ $item->rating >= $s ? 'text-amber-500/50 group-hover:text-amber-400 transition-colors duration-300' : 'text-zinc-300/30' }} sm:w-2.5 sm:h-2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                <p class="relative z-10 text-[11px] sm:text-xs text-muted-foreground leading-relaxed whitespace-normal line-clamp-3">"{{ $item->feedback }}"</p>
                            </div>
                        @endforeach
                    </div>
 
                    <!-- Radial Gradient Fades (Hanya nutupin Marquee) -->
                    <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-background to-transparent z-10 pointer-events-none"></div>
                    <div class="absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-background to-transparent z-10 pointer-events-none"></div>
                </div>
            </div>
            @endif

            <style>
                @keyframes marquee {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                .animate-marquee {
                    display: flex;
                    width: max-content;
                    animation: marquee 40s linear infinite;
                }
            </style>

            <!-- Pricelist Katalog Unit -->
            <div class="mt-12 w-full">
                <div x-data="{ visible: false }" x-intersect.once="visible = true"
                    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
                    class="text-center mb-12 mt-4 transition-all duration-1000 ease-out">
                    <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Katalog Harga
                        Sewa</h2>
                    <p class="mt-4 text-sm sm:text-base text-muted-foreground">Pilih unit terbaik yang sesuai dengan kebutuhan dan budget
                        Anda.</p>
                </div>

                @php
                    $occupiedUnits = \App\Models\Rental::where('status', 'paid')
                        ->where('waktu_selesai', '>', now())
                        ->with('units')
                        ->get()
                        ->flatMap(function($r) {
                            return $r->units->map(function($u) use ($r) {
                                return [
                                    'id' => $u->id,
                                    'returns_at' => $r->waktu_selesai
                                ];
                            });
                        })
                        ->pluck('returns_at', 'id');

                    $categorizedUnits = \App\Models\Unit::with('category')
                        ->where('is_active', true)
                        ->orderBy('category_id')
                        ->orderBy('seri')
                        ->get()
                        ->groupBy(function ($unit) {
                            return $unit->category ? $unit->category->name : 'Lainnya';
                        });
                @endphp

                <div class="space-y-16">
                    @foreach($categorizedUnits as $categoryName => $units)
                        @php $category = $units->first()->category; @endphp
                        <div x-data="{ expanded: false, visible: false }" x-intersect.once="visible = true"
                            :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'"
                            class="transition-all duration-1000 ease-out">
                            <div class="flex items-center gap-4 mb-6 mt-6">
                                <h3
                                    class="text-xl font-bold text-foreground whitespace-nowrap px-4 py-1.5 bg-blue-500/5 rounded-xl border border-blue-500/30">
                                    @if($category && $category->icon)
                                        <span class="mr-1">{{ $category->icon }}</span>
                                    @else
                                        @if(str_contains(strtolower($categoryName), 'iphone')) 
                                        @elseif(str_contains(strtolower($categoryName), 'playstation')) 🎮 @else 📦 @endif
                                    @endif
                                    {{ $categoryName }}
                                </h3>
                                <div class="h-px bg-border flex-1"></div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                @foreach($units as $unit)
                                    @php 
                                        $isIphone = $unit->category && str_contains(strtolower($unit->category->slug), 'iphone');
                                    @endphp
                                    <div
                                        :class="{ 'hidden sm:flex': !expanded && {{ $loop->index }} >= 4, 'flex': expanded || {{ $loop->index }} < 4 }"
                                        class="group relative bg-blue-500/[0.005] dark:bg-blue-500/[0.002] backdrop-blur-[4px] border-t border-l border-blue-500/30 rounded-2xl p-3 shadow-xl hover:border-blue-500/60 hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 ease-out flex-col justify-between overflow-hidden">
                                        
                                        @php
                                            $returnsAt = $occupiedUnits->get($unit->id);
                                            $isReady = !$returnsAt;
                                        @endphp

                                        <!-- Availability Badge (Liquid) -->
                                        <div class="absolute top-2 right-2 z-30 pointer-events-none">
                                            @if($isReady)
                                                <div class="px-2 py-0.5 rounded-full border-t border-l border-emerald-500/50 bg-emerald-500/10 backdrop-blur-md shadow-[inset_0_1px_1px_rgba(255,255,255,0.4)] flex items-center gap-1">
                                                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 tracking-tight">READY</span>
                                                </div>
                                            @else
                                                <div class="px-2 py-0.5 rounded-full border-t border-l border-rose-500/50 bg-rose-500/10 backdrop-blur-md shadow-[inset_0_1px_1px_rgba(255,255,255,0.4)] flex items-center gap-1">
                                                    <span class="flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                    <span class="text-[9px] font-bold text-rose-600 dark:text-rose-400 tracking-tight">
                                                        TERSEWA <span class="opacity-70 font-medium">({{ $returnsAt->format('H:i') }})</span>
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Specular Highlight Overlay -->
                                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-transparent pointer-events-none z-10"></div>

                                        <!-- Fluid Glow Blobs (Blue-Sky) -->
                                        <div class="absolute -right-8 -top-8 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 group-hover:scale-150 transition-all duration-1000 ease-in-out z-0"></div>
                                        <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-sky-500/5 rounded-full blur-3xl group-hover:bg-sky-500/15 group-hover:rotate-45 transition-all duration-1000 ease-in-out z-0"></div>
                                        
                                        <div class="relative z-20">
                                            <h4
                                                class="font-bold text-foreground text-sm group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight mb-1">
                                                {{ $unit->seri }}
                                            </h4>
                                            <div class="mt-1 space-y-0.5">
                                                @if($isIphone)
                                                    <p class="text-xs text-muted-foreground">{{ $unit->warna }} · {{ $unit->memori }}</p>
                                                @elseif($unit->specs)
                                                    <div class="flex flex-wrap gap-x-1.5 gap-y-0.5 mt-1">
                                                        @foreach($unit->specs as $key => $val)
                                                            @if($val)
                                                                <span
                                                                    class="text-[10px] bg-secondary/50 px-1.5 py-0.5 rounded text-secondary-foreground"><span
                                                                        class="font-bold opacity-70">{{ $key }}:</span> {{ $val }}</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if($unit->kondisi && !$isIphone)
                                                    <p class="text-[10px] text-muted-foreground italic mt-0.5 line-clamp-1">{{ $unit->kondisi }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-3 pt-2 border-t border-border/50">
                                            <div class="flex items-center justify-between">
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] text-muted-foreground uppercase font-medium leading-none mb-1">Sewa / Hari</span>
                                                    <div class="flex items-baseline gap-0.5">
                                                        <span class="text-[10px] font-bold text-foreground">Rp</span>
                                                        <span class="text-sm font-black text-foreground">{{ number_format($unit->harga_per_hari, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($units->count() > 4)
                                <div class="mt-6 flex justify-center sm:hidden">
                                    <button @click="expanded = !expanded" 
                                        class="inline-flex items-center gap-2 text-xs font-bold text-muted-foreground hover:text-blue-600 dark:hover:text-blue-400 transition-colors py-2.5 px-6 rounded-full border border-blue-500/20 bg-blue-500/5">
                                        <span x-text="expanded ? 'Sembunyikan' : 'Lihat Lebih Lengkap (+' + {{ $units->count() - 4 }} + ')'"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-300" :class="{ 'rotate-180': expanded }">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                    @endforeach
                </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-border py-8 bg-muted/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-muted-foreground">
            <p class="mb-3 font-medium">{{ \App\Models\Setting::getVal('admin_address', 'Jl. Jendral Sudirman,
                Purwokerto') }}</p>
            @php
                $ig_url = \App\Models\Setting::getVal('social_ig_url', '');
                $ig_name = \App\Models\Setting::getVal('social_ig_name', '');
                $tt_url = \App\Models\Setting::getVal('social_tiktok_url', '');
                $tt_name = \App\Models\Setting::getVal('social_tiktok_name', '');
            @endphp
            @if($ig_url || $tt_url)
                <div class="flex items-center justify-center gap-4 mb-4">
                    @if($ig_url)
                        <a href="{{ $ig_url }}" target="_blank" rel="noopener"
                            class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors group">
                            <!-- Instagram icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                class="group-hover:text-pink-500 transition-colors">
                                <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                            </svg>
                            <span class="text-xs">{{ $ig_name ?: 'Instagram' }}</span>
                        </a>
                    @endif
                    @if($tt_url)
                        <a href="{{ $tt_url }}" target="_blank" rel="noopener"
                            class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors group">
                            <!-- TikTok icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="currentColor" class="group-hover:text-foreground transition-colors">
                                <path
                                    d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.28 6.28 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.15a8.16 8.16 0 0 0 4.77 1.52V7.22a4.85 4.85 0 0 1-1-.53z" />
                            </svg>
                            <span class="text-xs">{{ $tt_name ?: 'TikTok' }}</span>
                        </a>
                    @endif
                </div>
            @endif
            &copy; {{ date('Y') }} RENT SPACE. All Rights Reserved.
        </div>
    </footer>

    @livewireScripts

    {{-- Social Proof Ticker Component --}}
    @if($recentRentals->count() > 0)
        <div x-data="{
                rentals: {{ json_encode($recentRentals) }},
                currentIndex: 0,
                show: false,
                init() {
                    if(this.rentals.length === 0) return;
                    
                    // Start cycling
                    setTimeout(() => { this.show = true; }, 5000);
                    
                    setInterval(() => {
                        this.show = false;
                        setTimeout(() => {
                            this.currentIndex = (this.currentIndex + 1) % this.rentals.length;
                            this.show = true;
                        }, 1000);
                    }, 15000);
                }
            }"
            class="fixed bottom-4 sm:bottom-6 left-4 sm:left-6 z-[60] pointer-events-none block max-w-[calc(100vw-2rem)] sm:max-w-md">
            
            <template x-if="rentals[currentIndex]">
                <div x-show="show"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 -translate-x-10 scale-90"
                    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                    x-transition:leave-end="opacity-0 -translate-x-10 scale-90"
                    class="group relative flex items-center gap-3 sm:gap-4 bg-white/5 dark:bg-zinc-950/20 backdrop-blur-[3px] backdrop-saturate-[180%] backdrop-contrast-[110%] border-t border-l border-white/60 border-r border-b border-zinc-950/30 p-2.5 sm:p-3 pr-5 sm:pr-6 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.2)] shadow-[inset_0_1px_1px_rgba(255,255,255,0.5),inset_0_-1px_1px_rgba(0,0,0,0.1)] pointer-events-auto overflow-hidden">
                    
                    <!-- 3D Bevel Shine -->
                    <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent pointer-events-none rounded-2xl z-20"></div>

                    <div class="relative flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl overflow-hidden transition-colors duration-500"
                        :class="{ 
                            'bg-emerald-500/20 border border-emerald-500/30': rentals[currentIndex].status === 'paid',
                            'bg-amber-500/20 border border-amber-500/30': rentals[currentIndex].status === 'pending',
                            'bg-rose-500/20 border border-rose-500/30': rentals[currentIndex].status === 'cancelled'
                        }">
                        <svg x-show="rentals[currentIndex].status === 'paid'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-emerald-500 animate-pulse sm:w-6 sm:h-6">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <svg x-show="rentals[currentIndex].status === 'pending'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-500 animate-spin sm:w-6 sm:h-6">
                             <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                         </svg>
                        <svg x-show="rentals[currentIndex].status === 'cancelled'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-rose-500 sm:w-6 sm:h-6">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </div>

                    <div class="flex flex-col">
                        <p class="text-[10px] sm:text-xs font-bold text-foreground leading-tight">
                            <span :class="{
                                'text-emerald-600 dark:text-emerald-400': rentals[currentIndex].status === 'paid',
                                'text-amber-600 dark:text-amber-400': rentals[currentIndex].status === 'pending',
                                'text-rose-600 dark:text-rose-400': rentals[currentIndex].status === 'cancelled'
                            }" x-text="rentals[currentIndex].name"></span>
                            <span class="text-muted-foreground/80 font-medium" x-text="rentals[currentIndex].action"></span>
                        </p>
                        <p class="text-[11px] sm:text-[13px] font-black text-foreground mt-0.5 tracking-tight uppercase line-clamp-1" x-text="rentals[currentIndex].unit"></p>
                        <p class="text-[9px] sm:text-[10px] text-muted-foreground/60 mt-0.5 font-medium flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sm:w-2.5 sm:h-2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span x-text="rentals[currentIndex].time"></span>
                        </p>
                    </div>

                    <!-- Small Live Indicator -->
                    <div class="absolute-inline flex h-2 w-2 rounded-full ml-auto self-start mt-1"
                        :class="{
                            'bg-emerald-500': rentals[currentIndex].status === 'paid',
                            'bg-amber-500': rentals[currentIndex].status === 'pending',
                            'bg-rose-500': rentals[currentIndex].status === 'cancelled'
                        }">
                        <span class="animate-ping absolute h-2 w-2 rounded-full opacity-75"
                            :class="{
                                'bg-emerald-500': rentals[currentIndex].status === 'paid',
                                'bg-amber-500': rentals[currentIndex].status === 'pending',
                                'bg-rose-500': rentals[currentIndex].status === 'cancelled'
                            }"></span>
                    </div>
                </div>
            </template>
        </div>
    @endif
</body>

</html>