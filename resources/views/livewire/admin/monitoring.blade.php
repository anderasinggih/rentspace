<div class="pb-10" x-data="{ 
    modalOpen: false, 
    dayWidth: 100,
    unitWidth: 180
}" @open-rental-modal.window="modalOpen = true"
:style="'--admin-day-width: ' + dayWidth + 'px; --admin-unit-width: ' + unitWidth + 'px;'">
    <style>
        .m-grid-wrapper {
            width: calc(var(--admin-unit-width) + (var(--admin-day-width) * {{ count($dates) }}));
            min-width: 100%;
        }

        .m-unit-col {
            width: var(--admin-unit-width);
            min-width: var(--admin-unit-width);
        }

        .m-day-col {
            width: var(--admin-day-width);
            min-width: var(--admin-day-width);
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div class="max-w-[98vw] mx-auto px-2 sm:px-4 pt-6">
        <!-- Tablet & Desktop Alert (Visible only on small phones) -->
        <div class="sm:hidden flex flex-col items-center justify-center py-20 text-center">
            <div class="h-20 w-20 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="10" x="2" y="3" rx="2"/><path d="M7 21h10"/><path d="M12 13v8"/></svg>
            </div>
            <h2 class="text-xl font-bold text-foreground ">Layar Terlalu Kecil</h2>
            <p class="text-muted-foreground mt-2 text-sm max-w-[280px]">Fitur monitoring timeline dioptimalkan untuk tampilan iPad, Tablet, atau Desktop.</p>
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="mt-8 px-6 py-2 bg-primary text-primary-foreground rounded-xl font-bold text-sm">Kembali</a>
        </div>

        <!-- Monitoring Content (Visible on iPad & Desktop) -->
        <div class="hidden sm:block">
            <!-- Filter Bar (Two-Row Layout for iPad/Tablet) -->
            <div class="flex flex-col gap-6 mb-8 bg-muted/20 p-4 md:p-6 rounded-2xl border border-border w-full">
                <!-- Row 1: Primary Filters -->
                <div class="flex flex-col sm:flex-row items-end gap-4 md:gap-6">
                    <!-- Category Dropdown -->
                    <div class="w-full sm:w-[240px]">
                        <label class="text-[10px] font-black text-muted-foreground/70  ml-1 mb-2 block uppercase tracking-wider">Filter Kategori</label>
                        <div class="relative">
                            <select wire:model.live="filterCategoryId" 
                                class="w-full h-10 pl-3 pr-10 bg-background border border-border rounded-xl text-xs font-bold shadow-sm focus:ring-2 focus:ring-primary/20 outline-none transition-all appearance-none cursor-pointer">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Timeframe Dropdown -->
                    <div class="w-full sm:w-[180px]">
                        <label class="text-[10px] font-black text-muted-foreground/70  ml-1 mb-2 block uppercase tracking-wider">Rentang Waktu</label>
                        <div class="relative">
                            <select wire:model.live="timeframe" 
                                class="w-full h-10 pl-3 pr-10 bg-background border border-border rounded-xl text-xs font-bold shadow-sm focus:ring-2 focus:ring-primary/20 outline-none transition-all appearance-none cursor-pointer">
                                <option value="7">7 Hari</option>
                                <option value="14">14 Hari</option>
                                <option value="month">Bulan Ini</option>
                                <option value="year">Tahun Ini</option>
                                <option value="all">Semua</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>
                    </div>

                    @if($timeframe === 'custom')
                        <div class="flex items-center gap-3 animate-in fade-in slide-in-from-left-4 duration-300">
                            <div class="w-[120px]">
                                <label class="text-[9px] font-bold text-muted-foreground/60 ml-1 mb-1 block">Dari</label>
                                <input type="date" wire:model.live="customStartDate" 
                                    class="w-full h-9 px-3 bg-background border border-border rounded-lg text-xs font-bold outline-none focus:border-primary">
                            </div>
                            <div class="w-[120px]">
                                <label class="text-[9px] font-bold text-muted-foreground/60 ml-1 mb-1 block">Sampai</label>
                                <input type="date" wire:model.live="customEndDate" 
                                    class="w-full h-9 px-3 bg-background border border-border rounded-lg text-xs font-bold outline-none focus:border-primary">
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Row 2: Zoom Controls (Full Width) -->
                <div class="flex flex-wrap items-center justify-between gap-6 bg-background rounded-xl p-3 md:p-4 border border-border shadow-sm w-full">
                    <!-- Day Width Slider -->
                    <div class="flex-1 min-w-[140px] flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="text-[8px] font-black text-muted-foreground uppercase tracking-widest">Timeline Zoom</label>
                            <span class="text-[8px] font-bold text-primary" x-text="dayWidth + 'px'"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="range" min="40" max="300" x-model="dayWidth" 
                                class="w-full accent-primary h-1 bg-muted rounded-full appearance-none cursor-pointer">
                        </div>
                    </div>

                    <div class="hidden md:block h-8 w-px bg-border/60"></div>

                    <!-- Unit Width Slider -->
                    <div class="flex-1 min-w-[140px] flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="text-[8px] font-black text-muted-foreground uppercase tracking-widest">Unit Area Width</label>
                            <span class="text-[8px] font-bold text-primary" x-text="unitWidth + 'px'"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="range" min="130" max="400" x-model="unitWidth" 
                                class="w-full accent-primary h-1 bg-muted rounded-full appearance-none cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN MONITORING GRID -->
            <div class="overflow-x-auto hide-scrollbar rounded-2xl border border-border bg-background shadow-2xl relative">
                <div class="m-grid-wrapper relative bg-background">
                    
                    <!-- HEADER DATES -->
                    <div class="flex-col border-b border-border bg-muted/10 sticky top-0 z-50 backdrop-blur-md">
                        {{-- First Row: Month & Year --}}
                        <div class="flex border-b border-border/30 overflow-visible">
                            <div class="m-unit-col shrink-0 border-r border-border border-dashed bg-background sticky left-0 z-[61] h-8 flex items-center justify-center">
                                <span class="font-black text-[8px] text-muted-foreground/30 uppercase  px-3">Timeline</span>
                            </div>
                            <div class="flex-1 flex pointer-events-none">
                                @php
                                    $currentMonth = null;
                                    $monthGroups = [];
                                    foreach($dates as $date) {
                                        $mKey = $date->format('M Y');
                                        if(!isset($monthGroups[$mKey])) $monthGroups[$mKey] = 0;
                                        $monthGroups[$mKey]++;
                                    }
                                @endphp
                                @foreach($monthGroups as $monthName => $count)
                                    <div class="shrink-0 h-8 flex items-center justify-center border-r border-border/10 bg-muted/5 group/monthHeader"
                                         style="width: calc(var(--admin-day-width) * {{ $count }});">
                                        <span class="text-[9px] font-black text-primary/60 ">{{ $monthName }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Second Row: Day & Date --}}
                        <div class="flex">
                            <div class="m-unit-col shrink-0 p-5 border-r border-border border-dashed flex items-center justify-center bg-background sticky left-0 z-[60] shadow-[4px_0_15px_-5px_rgba(0,0,0,0.1)]">
                                <span class="font-bold text-[10px]  text-primary/70">Fleet List</span>
                            </div>
                            <div class="flex-1 flex overflow-visible">
                                @foreach($dates as $date)
                                    <div class="m-day-col shrink-0 p-3 text-center border-r border-border/30 {{ $date->isToday() ? 'bg-primary/[0.08] shadow-[inset_0_0_15px_rgba(var(--primary),0.05)]' : '' }}">
                                        <div class="text-[11px] font-bold text-foreground leading-none {{ $date->isToday() ? 'text-primary' : '' }}">
                                            {{ $date->translatedFormat('D') }}
                                        </div>
                                        <div class="text-[9px] font-medium text-muted-foreground mt-1.5 text-center">
                                            {{ $date->format('d/m') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- BODY CONTENT - Flat List -->
                    <div class="flex flex-col relative divide-y divide-border/50">
                        @foreach($units as $unit)
                            <div class="flex min-h-[85px] group hover:bg-muted/[0.02] transition-colors relative border-b border-border/30 last:border-b-0">
                                <!-- Unit Column (Sticky) -->
                                <div class="m-unit-col shrink-0 p-4 md:p-5 border-r border-border border-dashed flex items-center gap-4 bg-background sticky left-0 z-40 transition-colors shadow-[4px_0_15px_-5px_rgba(0,0,0,0.08)]">
                                    <div class="w-1.5 h-10 rounded-full bg-primary/10 group-hover:bg-primary transition-all duration-300"></div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-[13px]  leading-tight truncate text-foreground group-hover:text-primary transition-colors">
                                            {{ $unit->seri }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-1.5 overflow-hidden">
                                            <span class="text-[8px] px-1.5 py-0.5 rounded-md bg-muted font-black text-muted-foreground flex-shrink-0">{{ $unit->category->name ?? 'Fleet' }}</span>
                                            <span class="text-[9px] font-medium text-muted-foreground/60 truncate er">{{ $unit->warna }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline Track -->
                                <div class="flex-1 relative">
                                    <!-- Background Grid Lines -->
                                    <div class="absolute inset-0 flex pointer-events-none">
                                        @foreach($dates as $date)
                                            <div class="m-day-col h-full border-r border-border/10 {{ $date->isToday() ? 'bg-primary/[0.02]' : '' }}"></div>
                                        @endforeach
                                    </div>

                                    <!-- Rental Bars Layer -->
                                    @foreach($unit->rentals as $rental)
                                        @php
                                            $sDate = \Carbon\Carbon::parse($rental->waktu_mulai);
                                            $eDate = \Carbon\Carbon::parse($rental->waktu_selesai);
                                            $isOngoing = now()->between($sDate, $eDate) && in_array($rental->status, ['paid', 'completed']);
                                            $isPaid = in_array($rental->status, ['paid', 'completed']);
                                            
                                            $viewStart = $dates[0]->startOfDay();
                                            $viewEnd = end($dates)->endOfDay();

                                            $effectiveStart = $sDate->lt($viewStart) ? $viewStart : $sDate;
                                            $effectiveEnd = $eDate->gt($viewEnd) ? $viewEnd : $eDate;

                                            if ($effectiveStart->gt($viewEnd) || $effectiveEnd->lt($viewStart)) continue;

                                            $startIndex = $viewStart->diffInMinutes($effectiveStart) / 1440;
                                            $duration = $effectiveStart->diffInMinutes($effectiveEnd) / 1440;
                                            
                                            // Ensure very short rentals are still visible
                                            $duration = max($duration, 0.01);
                                            
                                            $statusStyle = match($rental->status) {
                                                'paid' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 shadow-[0_4px_12px_rgba(16,185,129,0.08)]',
                                                'pending' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 shadow-[0_4px_12px_rgba(245,158,11,0.08)]',
                                                'completed' => 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20 shadow-[0_4px_12px_rgba(59,130,246,0.08)]',
                                                default => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/20',
                                            };
                                            
                                            $dotColor = match($rental->status) {
                                                'paid' => 'bg-emerald-500',
                                                'pending' => 'bg-amber-500',
                                                'completed' => 'bg-blue-500',
                                                default => 'bg-slate-500',
                                            };
                                        @endphp
                                        <div wire:click="selectRental({{ $rental->id }})"
                                            class="absolute h-full top-0 px-[4.5px] py-[15px] z-30 group/bar cursor-pointer"
                                            style="left: calc(var(--admin-day-width) * {{ $startIndex }}); width: calc(var(--admin-day-width) * {{ $duration }});"
                                            x-data="{ 
                                                timeLeft: '',
                                                endTime: {{ $eDate->timestamp }},
                                                isPaid: {{ $isPaid ? 'true' : 'false' }},
                                                updateCountdown() {
                                                    if (!this.isPaid) return;
                                                    const now = Math.floor(Date.now() / 1000);
                                                    const diff = this.endTime - now;
                                                    if (diff <= 0) {
                                                        this.timeLeft = 'Selesai';
                                                        return;
                                                    }
                                                    const h = Math.floor(diff / 3600);
                                                    const m = Math.floor((diff % 3600) / 60);
                                                    const s = diff % 60;
                                                    this.timeLeft = `${h}j ${m}m ${s}d`;
                                                }
                                            }" 
                                            x-init="updateCountdown(); if(isPaid) setInterval(() => updateCountdown(), 1000)">
                                            
                                            <div class="w-full h-full rounded-xl {{ $statusStyle }} px-3.5 py-1.5 flex flex-col justify-center transition-all relative overflow-hidden group-hover/bar:border-primary group-hover/bar:shadow-2xl group-hover/bar:z-50 ring-1 ring-transparent hover:ring-primary/40">
                                                <div class="flex items-center gap-2.5 overflow-hidden transition-all group-hover/bar:-translate-y-3">
                                                    <div class="h-1.5 w-1.5 rounded-full {{ $dotColor }} {{ $rental->status == 'pending' ? 'animate-pulse' : '' }} shrink-0"></div>
                                                    <div class="min-w-0">
                                                        <span class="text-[10px] font-black truncate leading-none  block">
                                                            {{ $rental->nama }}
                                                        </span>
                                                        @if($isPaid)
                                                            <span class="text-[8px] font-bold opacity-60 flex items-center gap-1 mt-0.5 group-hover/bar:opacity-0 transition-opacity">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                                <span x-text="timeLeft"></span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Detail on hover -->
                                                <div class="absolute inset-x-0 bottom-2.5 opacity-0 group-hover/bar:opacity-100 transition-all translate-y-4 group-hover/bar:translate-y-0 flex flex-col items-center justify-center gap-1">
                                                    <div class="flex items-center gap-2.5 text-[11px] font-black text-primary">
                                                        <span>{{ $sDate->format('H:i') }}</span>
                                                        <span class="opacity-30">→</span>
                                                        <span>{{ $eDate->format('H:i') }}</span>
                                                    </div>
                                                    @if($isPaid)
                                                        <span class="text-[8px] font-bold bg-primary/10 px-2 py-0.5 rounded-full text-primary flex items-center gap-1">
                                                            Sisa: <span x-text="timeLeft"></span>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- MONITORING LEGEND -->
            <div class="mt-10 flex flex-wrap items-center justify-center gap-12 bg-muted/5 p-5 rounded-2xl border border-border">
                <div class="flex items-center gap-3">
                    <div class="w-3.5 h-3.5 rounded bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.3)]"></div>
                    <span class="text-[10px] font-bold text-muted-foreground ">Aktif / Sudah Bayar</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-3.5 h-3.5 rounded bg-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.3)] animate-pulse"></div>
                    <span class="text-[10px] font-bold text-muted-foreground ">Antrean / Pending</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-3.5 h-3.5 rounded bg-blue-500 shadow-[0_0_12px_rgba(59,130,246,0.3)]"></div>
                    <span class="text-[10px] font-bold text-muted-foreground ">Kembali / Selesai</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Inspect Detail Modal -->
    <div x-show="modalOpen" 
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak>
        
        <div class="fixed inset-0" @click="modalOpen = false"></div>

        <div class="relative bg-background border border-border shadow-2xl rounded-2xl w-full max-w-xl overflow-hidden animate-in zoom-in duration-200"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">
            
            @if($this->selectedRental)
                @php $r = $this->selectedRental; @endphp
                <!-- Header -->
                <div class="p-6 border-b border-border flex justify-between items-start bg-muted/5">
                    <div>
                        <h3 class="text-lg font-bold  text-foreground">Detail Transaksi</h3>
                        <p class="text-[11px] text-muted-foreground mt-0.5 italic opacity-70">INV-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }} • {{ $r->created_at->format('d M Y') }}</p>
                    </div>
                    <button @click="modalOpen = false" class="rounded-md p-1.5 hover:bg-muted transition-colors border border-border bg-background">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[75vh] space-y-8 scrollbar-hide">
                    <!-- Status Section -->
                    <div class="flex items-center justify-between p-4 bg-muted/30 rounded-lg border border-border">
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold uppercase text-muted-foreground">Status Transaksi</p>
                            <div class="flex items-center gap-2">
                                @if($r->status === 'pending') <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span> <span class="text-sm font-semibold text-amber-600 dark:text-amber-400 uppercase">Menunggu Pembayaran</span>
                                @elseif($r->status === 'paid') <span class="h-2 w-2 rounded-full bg-blue-500"></span> <span class="text-sm font-semibold text-blue-600 dark:text-blue-400 uppercase">Dibayar (Aktif)</span>
                                @elseif($r->status === 'completed') <span class="h-2 w-2 rounded-full bg-green-500"></span> <span class="text-sm font-semibold text-green-600 dark:text-green-400 uppercase">Selesai</span>
                                @else <span class="h-2 w-2 rounded-full bg-red-500"></span> <span class="text-sm font-semibold text-red-600 dark:text-red-400 uppercase">Dibatalkan</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right space-y-1 border-l border-border pl-4">
                            <p class="text-[10px] font-bold uppercase text-muted-foreground">Booking Code</p>
                            <p class="text-sm font-black text-primary uppercase ">{{ $r->booking_code }}</p>
                        </div>
                    </div>

                    <!-- Customer & Unit Grid -->
                    <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Customer -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black uppercase  text-muted-foreground/70">Informasi Penyewa</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Nama Lengkap</p>
                                    <p class="text-sm font-semibold text-foreground leading-tight">{{ $r->nama }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Kontak WhatsApp</p>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $r->no_wa) }}" target="_blank" class="text-sm font-bold text-primary hover:underline italic">{{ $r->no_wa }}</a>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Identitas (NIK)</p>
                                    <p class="text-sm font-medium text-foreground ">{{ $r->nik }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Rental Info -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black uppercase  text-muted-foreground/70">Detail Unit & Waktu</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Unit Yang Disewa</p>
                                    <div class="space-y-2 mt-1">
                                        @foreach($r->units as $u)
                                            <div class="p-2 rounded bg-muted/50 border border-border/50">
                                                <p class="text-sm font-bold text-foreground">{{ $u->seri }}</p>
                                                <p class="text-[10px] text-muted-foreground uppercase">{{ $u->warna }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Jadwal Mulai</p>
                                    <p class="text-sm font-semibold text-foreground">{{ $r->waktu_mulai->format('d M Y, H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Jadwal Selesai</p>
                                    <p class="text-sm font-semibold text-foreground">{{ $r->waktu_selesai->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h4 class="text-[10px] font-black uppercase  text-muted-foreground/70">Ikhtisar Pembayaran</h4>
                        <div class="rounded-lg border border-border overflow-hidden">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-border">
                                    <tr class="bg-muted/10">
                                        <td class="py-2.5 px-4 text-muted-foreground text-xs">Harga Dasar Sewa</td>
                                        <td class="py-2.5 px-4 text-right font-medium text-xs">Rp {{ number_format($r->subtotal_harga, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($r->potongan_diskon > 0)
                                        <tr>
                                            <td class="py-2.5 px-4 text-red-500 font-medium text-xs">Potongan Diskon / Promo</td>
                                            <td class="py-2.5 px-4 text-right font-medium text-red-500 text-xs">- Rp {{ number_format($r->potongan_diskon, 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="py-2.5 px-4 text-muted-foreground text-xs">Kode Unik / Service Fee</td>
                                        <td class="py-2.5 px-4 text-right font-medium text-xs">+ Rp {{ $r->kode_unik_pembayaran }}</td>
                                    </tr>
                                    @if($r->denda > 0 || $r->denda_kerusakan > 0)
                                        <tr class="bg-amber-500/5">
                                            <td class="py-2.5 px-4 text-amber-600 font-bold text-xs">Total Denda</td>
                                            <td class="py-2.5 px-4 text-right font-bold text-amber-600 text-xs">+ Rp {{ number_format($r->denda + $r->denda_kerusakan, 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                    <tr class="bg-primary/5">
                                        <td class="py-4 px-4 font-bold text-foreground">TOTAL AKHIR</td>
                                        <td class="py-4 px-4 text-right text-lg font-black text-primary">Rp {{ number_format($r->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-border flex justify-end gap-3 bg-muted/5">
                    <a href="{{ route('admin.transactions') }}?search={{ $r->booking_code }}" wire:navigate 
                        class="h-10 px-4 rounded-md border border-input bg-background flex items-center justify-center text-xs font-semibold hover:bg-muted transition-all">Riwayat Transaksi</a>
                    <button @click="modalOpen = false" 
                        class="h-10 px-8 rounded-md bg-primary text-primary-foreground shadow shadow-primary/20 flex items-center justify-center text-sm font-semibold hover:bg-primary/90 transition-all">Selesai Meninjau</button>
                </div>
            @else
                <div class="p-24 text-center">
                    <div class="animate-spin h-10 w-10 border-4 border-primary border-t-transparent rounded-full mx-auto mb-6"></div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase  opacity-80 italic animate-pulse">Menghubungkan Server...</p>
                </div>
            @endif
        </div>
    </div>
</div>
