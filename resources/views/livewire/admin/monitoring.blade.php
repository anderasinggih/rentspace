<div class="pb-10" x-data="{ 
    modalOpen: false, 
    dayWidth: 100,
    unitWidth: 180
}" @open-rental-modal.window="modalOpen = true"
    :style="'--admin-day-width: ' + dayWidth + 'px; --admin-unit-width: ' + unitWidth + 'px;'">
    <style>
        .m-grid-wrapper {
            width: calc(var(--admin-unit-width) + (var(--admin-day-width) *
                        {{ count($dates) }}
                    ));
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

    <div>
        {{-- Header & Navigation Switcher --}}
        <div class="flex items-center justify-between mb-3 sm:mb-6">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold  text-foreground">Monitoring</h1>
                <p class="mt-2 text-sm text-muted-foreground">Monitor and track all orders.</p>
            </div>

            <div class="flex items-center gap-1 bg-muted/40 backdrop-blur-sm border border-border p-1 rounded-xl shadow-sm">
                <button class="px-4 py-1.5 text-[11px] font-bold bg-background border border-border/50 rounded-lg shadow-sm">Monitor</button>
                <a href="{{ route('admin.radar') }}" class="px-4 py-1.5 text-[11px] font-semibold hover:bg-muted/60 rounded-lg transition-all opacity-60">Radar</a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div
            class="hidden sm:flex flex-col gap-4 sm:gap-6 mb-6 sm:mb-8 bg-muted/20 p-4 sm:p-6 rounded-2xl border border-border w-full">
            <!-- Row 1: Primary Filters -->
            <div class="flex flex-col sm:flex-row items-end gap-4 md:gap-6">
                <!-- Category Dropdown -->
                <div class="w-full sm:w-[200px]">
                    <label class="text-[9px] font-bold text-muted-foreground/70 ml-1 mb-2 block tracking-wider">Filter
                        Kategori</label>
                    <div class="relative">
                        <select wire:model.live="filterCategoryId"
                            class="w-full h-10 pl-3 pr-10 bg-background border border-border rounded-xl text-xs font-bold shadow-sm focus:ring-2 focus:ring-primary/20 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round" class="opacity-50">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </div>
                </div>
                <!-- Timeframe Dropdown (Only relevant for Timeline, but keeping it) -->
                <div class="w-full sm:w-[180px] hidden sm:block">
                    <label class="text-[9px] font-bold text-muted-foreground/70 ml-1 mb-2 block tracking-wider">Rentang
                        Waktu</label>
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
                        <div
                            class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round" class="opacity-50">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </div>
                </div>

                @if($timeframe === 'custom')
                    <div class="hidden sm:flex items-center gap-3 animate-in fade-in slide-in-from-left-4 duration-300">
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

            <!-- Row 2: View Controls (Zoom) -->
            <div class="flex flex-wrap items-center gap-4 sm:gap-8 pt-4 border-t border-border/50">
                <!-- Day Width Slider -->
                <div class="flex-1 min-w-[180px] flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[9px] font-bold text-muted-foreground/70 tracking-wider">Zoom
                            Timeline</label>
                        <span class="text-[9px] font-bold text-primary" x-text="dayWidth + 'px'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="range" min="40" max="300" x-model="dayWidth"
                            class="w-full accent-primary h-1 bg-muted rounded-full appearance-none cursor-pointer">
                    </div>
                </div>

                <div class="hidden md:block h-8 w-px bg-border/20"></div>

                <!-- Unit Width Slider -->
                <div class="flex-1 min-w-[180px] flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[9px] font-bold text-muted-foreground/70 tracking-wider">Lebar Kolom
                            Unit</label>
                        <span class="text-[9px] font-bold text-primary" x-text="unitWidth + 'px'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="range" min="130" max="400" x-model="unitWidth"
                            class="w-full accent-primary h-1 bg-muted rounded-full appearance-none cursor-pointer">
                    </div>
                </div>
            </div>

        </div>

        <!-- MAIN MONITORING GRID (Hidden on mobile) -->
        <div
            class="hidden sm:block overflow-x-auto hide-scrollbar rounded-2xl border border-border bg-background shadow-2xl relative">

            <div class="m-grid-wrapper relative bg-background">

                <!-- HEADER DATES -->
                <div class="flex flex-col border-b border-border bg-muted/10 sticky top-0 z-50 backdrop-blur-md">
                    {{-- First Row: Month & Year --}}
                    <div class="flex border-b border-border/30 overflow-visible">
                        <div
                            class="m-unit-col shrink-0 border-r border-border border-dashed bg-background sticky left-0 z-[61] h-8 flex items-center justify-center">
                            <span class="font-black text-[8px] text-muted-foreground/30 px-3">Timeline</span>
                        </div>
                        <div class="flex-1 flex pointer-events-none">
                            @php
                                $currentMonth = null;
                                $monthGroups = [];
                                foreach ($dates as $date) {
                                    $mKey = $date->format('M Y');
                                    if (!isset($monthGroups[$mKey]))
                                        $monthGroups[$mKey] = 0;
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
                        <div
                            class="m-unit-col shrink-0 p-5 border-r border-border border-dashed flex items-center justify-center bg-background sticky left-0 z-[60] shadow-[4px_0_15px_-5px_rgba(0,0,0,0.1)]">
                            <span class="font-bold text-[10px]  text-primary/70">Fleet List</span>
                        </div>
                        <div class="flex-1 flex overflow-visible">
                            @foreach($dates as $date)
                                <div
                                    class="m-day-col shrink-0 p-3 text-center border-r border-border/30 {{ $date->isToday() ? 'bg-primary/10 shadow-[inset_0_0_15px_rgba(var(--primary),0.02)]' : ($date->isWeekend() ? 'bg-rose-500/5' : '') }}">
                                    <div
                                        class="text-[11px] font-bold text-foreground leading-none {{ $date->isToday() ? 'text-primary' : '' }}">
                                        {{ $date->locale('id')->translatedFormat('D') }}
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
                        <div
                            class="flex min-h-[70px] group hover:bg-muted/[0.02] transition-colors relative border-b border-border/30 last:border-b-0">
                            <!-- Unit Column (Sticky) -->
                            <div
                                class="m-unit-col shrink-0 p-3 md:p-4 border-r border-border border-dashed flex items-center gap-4 bg-background sticky left-0 z-40 transition-colors shadow-[4px_0_15px_-5px_rgba(0,0,0,0.08)]">
                                <div
                                    class="w-1.5 h-8 rounded-full bg-primary/10 group-hover:bg-primary transition-all duration-300">
                                </div>
                                <div class="min-w-0">
                                    <div
                                        class="font-bold text-[13px]  leading-tight truncate text-foreground group-hover:text-primary transition-colors">
                                        {{ $unit->seri }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-1.5 overflow-hidden">
                                        <span
                                            class="text-[8px] px-1.5 py-0.5 rounded-md bg-muted font-black text-muted-foreground flex-shrink-0">{{ $unit->category->name ?? 'Fleet' }}</span>
                                        <span
                                            class="text-[9px] font-medium text-muted-foreground/60 truncate">{{ $unit->warna }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline Track -->
                            <div class="flex-1 relative">
                                <!-- Background Grid Lines -->
                                <div class="absolute inset-0 flex pointer-events-none">
                                    @foreach($dates as $date)
                                        <div
                                            class="m-day-col h-full shrink-0 border-r border-border/30 {{ $date->isToday() ? 'bg-primary/[0.04]' : ($date->isWeekend() ? 'bg-rose-500/[0.03]' : '') }}">
                                        </div>
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

                                        if ($effectiveStart->gt($viewEnd) || $effectiveEnd->lt($viewStart))
                                            continue;

                                        $startIndex = $viewStart->diffInMinutes($effectiveStart) / 1440;
                                        $duration = $effectiveStart->diffInMinutes($effectiveEnd) / 1440;

                                        // Ensure very short rentals are still visible
                                        $duration = max($duration, 0.01);

                                        $statusStyle = match ($rental->status) {
                                            'paid' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 shadow-[0_4px_12px_rgba(16,185,129,0.08)]',
                                            'pending' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 shadow-[0_4px_12px_rgba(245,158,11,0.08)]',
                                            'completed' => 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20 shadow-[0_4px_12px_rgba(59,130,246,0.08)]',
                                            default => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/20',
                                        };

                                        $dotColor = match ($rental->status) {
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

                                        <div
                                            class="w-full h-full rounded-xl {{ $statusStyle }} px-3.5 py-1.5 flex flex-col justify-center transition-all relative overflow-hidden group-hover/bar:border-primary group-hover/bar:shadow-2xl group-hover/bar:z-50 ring-1 ring-transparent hover:ring-primary/40">
                                            <div
                                                class="flex items-center gap-2.5 overflow-hidden transition-all group-hover/bar:-translate-y-3">
                                                <div
                                                    class="h-1.5 w-1.5 rounded-full {{ $dotColor }} {{ $rental->status == 'pending' ? 'animate-pulse' : '' }} shrink-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="text-[10px] font-black truncate leading-none block">
                                                        {{ $rental->nama }}
                                                    </span>
                                                    @if($isPaid)
                                                        <span
                                                            class="text-[8px] font-bold opacity-60 flex items-center gap-1 mt-0.5 group-hover/bar:opacity-0 transition-opacity">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="10" />
                                                                <polyline points="12 6 12 12 16 14" />
                                                            </svg>
                                                            <span x-text="timeLeft"></span>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Detail on hover -->
                                            <div
                                                class="absolute inset-x-0 bottom-2.5 opacity-0 group-hover/bar:opacity-100 transition-all translate-y-4 group-hover/bar:translate-y-0 flex flex-col items-center justify-center gap-1">
                                                <div class="flex items-center gap-2.5 text-[11px] font-black text-primary">
                                                    <span>{{ $sDate->format('H:i') }}</span>
                                                    <span class="opacity-30">→</span>
                                                    <span>{{ $eDate->format('H:i') }}</span>
                                                </div>
                                                @if($isPaid)
                                                    <span
                                                        class="text-[8px] font-bold bg-primary/10 px-2 py-0.5 rounded-full text-primary flex items-center gap-1">
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

        <!-- MONITORING LEGEND (Timeline Only) -->
        <div
            class="mt-10 hidden sm:flex flex-wrap items-center justify-center gap-12 bg-muted/5 p-5 rounded-2xl border border-border dark:border-white/5 shadow-inner">
            <div class="flex items-center gap-3">
                <div class="w-3.5 h-3.5 rounded bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.3)]"></div>
                <span class="text-[9px] font-bold text-muted-foreground tracking-widest">Aktif / Sudah Bayar</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-3.5 h-3.5 rounded bg-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.3)] animate-pulse">
                </div>
                <span class="text-[9px] font-bold text-muted-foreground tracking-widest">Antrean / Pending</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-3.5 h-3.5 rounded bg-blue-500 shadow-[0_0_12px_rgba(59,130,246,0.3)]"></div>
                <span class="text-[9px] font-bold text-muted-foreground tracking-widest">Kembali / Selesai</span>
            </div>
        </div>

        <!-- STATUS CATEGORIES SECTION -->
        <div class="mt-4 sm:mt-10 grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-10 animate-in fade-in slide-in-from-bottom-10 duration-700">

            <!-- 1. ACTIVE RENTALS SECTION -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-border dark:border-white/10 pb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base sm:text-xl font-black text-foreground tracking-tight leading-none">Unit
                                Sedang Disewa</h2>
                            <p class="text-[9px] sm:text-xs text-muted-foreground mt-1">Unit aktif yang sedang
                                digunakan.</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 px-3 sm:px-4 py-1.5 sm:py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full shrink-0">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span
                            class="text-[9px] sm:text-[11px] font-black text-emerald-600 dark:text-emerald-400 tracking-wider">{{ $activeRentals->count() }}
                            Aktif</span>
                    </div>
                </div>

                @if($activeRentals->count() > 0)
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($activeRentals as $rental)
                            <div x-data="{ expanded: false }"
                                class="bg-card border border-border rounded-2xl overflow-hidden transition-all duration-300 hover:border-emerald-500/30"
                                :class="expanded ? 'shadow-2xl ring-1 ring-emerald-500/20' : 'shadow-sm'">

                                <!-- Accordion Header -->
                                <div @click="expanded = !expanded"
                                    class="p-2 sm:p-3 md:p-4 flex items-center justify-between gap-2 cursor-pointer bg-background hover:bg-muted/5 transition-colors">
                                    <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                                        <div class="flex -space-x-3 overflow-hidden shrink-0">
                                            @foreach($rental->units as $u)
                                                <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-lg bg-muted border border-background flex items-center justify-center shadow-sm"
                                                    title="{{ $u->seri }}">
                                                    <span
                                                        class="text-[7px] font-bold text-muted-foreground leading-none text-center px-0.5">
                                                        {{ substr($u->seri, 0, 3) }}<br>{{ substr($u->seri, -2) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <h3
                                                    class="font-bold text-emerald-600 dark:text-emerald-400 text-[10px] sm:text-sm truncate tracking-tight">
                                                    {{ $rental->units->pluck('seri')->join(', ') }}</h3>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="text-[10px] sm:text-sm font-bold text-foreground truncate">{{ explode(' ', trim($rental->nama))[0] }}</span>
                                                <span class="hidden sm:inline-block h-1 w-1 rounded-full bg-border"></span>
                                                <span class="text-[10px] sm:text-xs text-muted-foreground truncate">
                                                    {{ $rental->waktu_mulai->format('H:i') }} -
                                                    {{ $rental->waktu_selesai->format('H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 sm:gap-6">
                                        <!-- Countdown Column -->
                                        <div class="text-right w-20 sm:w-32 shrink-0 pr-2 sm:pr-4 border-r border-border/50"
                                            x-data="{ 
                                                            timeLeft: '',
                                                            endTime: {{ $rental->waktu_selesai->timestamp }},
                                                            update() {
                                                                const now = Math.floor(Date.now() / 1000);
                                                                const diff = this.endTime - now;
                                                                if (diff <= 0) { this.timeLeft = 'Selesai'; return; }
                                                                const h = Math.floor(diff / 3600);
                                                                const m = Math.floor((diff % 3600) / 60);
                                                                this.timeLeft = `${h}j ${m}m`;
                                                            }
                                                        }" x-init="update(); setInterval(() => update(), 60000)">
                                            <p
                                                class="text-[7px] sm:text-[8px] font-black text-muted-foreground tracking-widest">
                                                Sisa</p>
                                            <p x-text="timeLeft"
                                                class="text-sm sm:text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                                            </p>
                                        </div>
                                        <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-muted/30 border border-border flex items-center justify-center text-muted-foreground transition-transform duration-300"
                                            :class="expanded ? 'rotate-180 bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : ''">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Accordion content -->
                                <div x-show="expanded" x-collapse class="bg-muted/20 border-t border-border">
                                    <div class="p-3 md:p-4 grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                                        {{-- Kolom 1: Data Diri --}}
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <p class="text-sm font-bold text-white leading-tight">
                                                    {{ $rental->nama }}</p>
                                                @if($rental->sosial_media)
                                                    <span class="text-[10px] font-bold text-sky-400 transition-colors cursor-default">@ {{ $rental->sosial_media }}</span>
                                                @endif
                                                <x-ui.badge variant="blue" class="text-[9px] uppercase tracking-wider">Active</x-ui.badge>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-[9px] font-bold text-white/40 uppercase leading-none tracking-wider">NIK / Identitas</p>
                                                    <p class="text-xs font-medium text-white/90 mt-1.5">{{ $rental->nik }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-bold text-white/40 uppercase leading-none tracking-wider">Booking Code</p>
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center rounded border bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200/50 dark:border-sky-900/50 px-2 py-0.5 font-mono text-[10px] font-bold uppercase tracking-tight">
                                                            {{ $rental->booking_code }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-white/40 uppercase leading-none tracking-wider">Alamat Lengkap</p>
                                                <p class="text-xs font-medium text-white/90 leading-tight mt-1.5">
                                                    {{ $rental->alamat ?: '-' }}</p>
                                            </div>
                                            
                                            <div class="pt-2 flex flex-wrap items-center gap-2">
                                                @if ($rental->status_bayar === 'Menunggu')
                                                    <button wire:click="markAsPaid({{ $rental->id }})"
                                                        wire:loading.attr="disabled"
                                                        class="px-4 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-[9px] font-bold hover:bg-emerald-500 hover:text-white transition-all shadow-sm">Validasi</button>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Kolom 2: Waktu --}}
                                        <div class="space-y-4">
                                            <h4 class="text-[10px] font-bold text-white/40 tracking-widest uppercase mb-1">Jadwal Sewa</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="flex items-stretch gap-2.5">
                                                    <div class="w-1 bg-sky-500 rounded-full shrink-0 my-0.5 shadow-[0_0_8px_rgba(14,165,233,0.4)]"></div>
                                                    <div class="flex flex-col justify-center">
                                                        <p class="text-[9px] font-bold text-white/30 uppercase leading-none mb-1.5">Mulai</p>
                                                        <p class="text-[11px] font-bold text-white leading-tight">
                                                            {{ $rental->waktu_mulai->translatedFormat('d M, H:i') }}<br>
                                                            <span class="text-[10px] font-semibold text-sky-400 uppercase tracking-tighter">{{ $rental->waktu_mulai->translatedFormat('l') }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-stretch gap-2.5">
                                                    <div class="w-1 bg-emerald-500 rounded-full shrink-0 my-0.5 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></div>
                                                    <div class="flex flex-col justify-center">
                                                        <p class="text-[9px] font-bold text-white/30 uppercase leading-none mb-1.5">Selesai</p>
                                                        <p class="text-[11px] font-bold text-white leading-tight">
                                                            {{ $rental->waktu_selesai->translatedFormat('d M, H:i') }}<br>
                                                            <span class="text-[10px] font-semibold text-emerald-400 uppercase tracking-tighter">{{ $rental->waktu_selesai->translatedFormat('l') }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Kolom 3: Biaya --}}
                                        <div class="space-y-4">
                                            <div class="bg-background rounded-xl p-3 sm:p-4 border border-border/50">
                                                <div class="space-y-2">
                                                    <div class="flex justify-between text-[11px]">
                                                        <span class="text-muted-foreground">Harga Sewa</span>
                                                        <span class="font-semibold">Rp
                                                            {{ number_format($rental->subtotal_harga, 0, ',', '.') }}</span>
                                                    </div>
                                                    @if($rental->potongan_diskon > 0)
                                                        <div class="flex justify-between text-[11px]">
                                                            <span class="text-rose-500">Total Diskon</span>
                                                            <span class="font-semibold text-rose-500">- Rp
                                                                {{ number_format($rental->potongan_diskon, 0, ',', '.') }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="h-px bg-border my-2"></div>
                                                    <div class="flex justify-between items-center pt-1">
                                                        <span class="text-[9px] font-bold text-foreground">Grand Total</span>
                                                        <span class="text-sm font-black text-primary">Rp
                                                            {{ number_format($rental->grand_total, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Action Button -->
                                            <div class="pt-2 flex gap-2">
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $rental->no_wa) }}"
                                                    target="_blank"
                                                    class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-emerald-500/5 text-emerald-600 border border-emerald-500/10 text-[9px] font-bold hover:bg-emerald-500 hover:text-white transition-all overflow-hidden whitespace-nowrap">
                                                    WhatsApp
                                                </a>
                                                <button wire:click="openDendaModal({{ $rental->id }})"
                                                    class="flex-[2] flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-blue-500/10 text-blue-600 border border-blue-500/20 text-[9px] font-black hover:bg-blue-500 hover:text-white transition-all shadow-sm active:scale-95 overflow-hidden whitespace-nowrap">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                    Selesaikan
                                                </button>
                                            </div>

                                            </div>
                                        </div>

                                    {{-- Log Lokasi: Hanya muncul jika ada iPhone dan ada datanya --}}
                                    @php 
                                        $iphoneUnits = $rental->units->filter(fn($u) => $u->category && str_contains(strtolower($u->category->name), 'iphone'));
                                        $hasLogs = false;
                                        if($iphoneUnits->isNotEmpty()) {
                                            foreach($iphoneUnits as $u) {
                                                if($u->locations()->whereBetween('created_at', [$rental->waktu_mulai, $rental->waktu_selesai])->exists()) {
                                                    $hasLogs = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if($hasLogs)
                                        <div class="px-3 pb-4 sm:px-6">
                                            <div class="pt-4 border-t border-white/5">
                                                <div class="space-y-1 max-h-[185px] overflow-y-auto pr-2 scrollbar-hide">
                                                    @foreach($iphoneUnits as $u)
                                                        @php 
                                                            $logs = $u->locations()
                                                                ->whereBetween('created_at', [$rental->waktu_mulai, $rental->waktu_selesai])
                                                                ->latest()
                                                                ->limit(50)
                                                                ->get();
                                                        @endphp

                                                        @foreach($logs as $loc)
                                                            <div class="py-1.5 px-2.5 bg-white/[0.02] rounded-xl border border-white/5 flex items-center justify-between group/loc hover:bg-white/5 transition-all mb-1 last:mb-0">
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex items-center gap-1.5 mb-0.5">
                                                                        <span class="text-[10px] sm:text-xs font-semibold text-white/90 leading-none">{{ $loc->created_at->format('H:i') }}</span>
                                                                        <span class="text-[8px] sm:text-[9px] font-medium text-white/30 leading-none tracking-tight">{{ $loc->created_at->translatedFormat('d M Y') }}</span>
                                                                        <span class="text-[7px] sm:text-[8px] font-medium text-white/10 hidden sm:inline">· {{ $loc->created_at->diffForHumans() }}</span>
                                                                    </div>
                                                                    @if($loc->address)
                                                                        <p class="text-[9px] sm:text-[10px] text-emerald-500/60 font-medium leading-tight truncate pr-2">{{ $loc->address }}</p>
                                                                    @else
                                                                        <p class="text-[8px] sm:text-[9px] text-white/20 truncate italic font-light">{{ $loc->lat }}, {{ $loc->lng }}</p>
                                                                    @endif
                                                                </div>
                                                                
                                                                <div class="flex items-center gap-3 shrink-0">
                                                                    @if($loc->battery_level)
                                                                        <div class="flex flex-col items-center">
                                                                            <span class="text-[9px] sm:text-[10px] font-semibold {{ (int)$loc->battery_level < 20 ? 'text-rose-500' : 'text-emerald-500/40' }}">{{ (int)$loc->battery_level }}%</span>
                                                                        </div>
                                                                    @endif
                                                                    <a href="{{ route('admin.radar') }}?unit_id={{ $u->id }}" class="h-7 w-7 rounded-lg bg-white/5 text-white/20 flex items-center justify-center hover:bg-emerald-500 hover:text-white border border-white/5 transition-all">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        @endforeach

                    </div>
                @else
                    <div
                        class="py-12 bg-muted/5 border border-border border-dashed rounded-3xl flex flex-col items-center text-center">
                        <p class="text-[10px] font-bold text-muted-foreground opacity-50 tracking-widest">Tidak Ada Unit
                            Yang Sedang Disewa</p>
                    </div>
                @endif
            </div>

            <!-- 2. UPCOMING RENTALS SECTION -->
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-border dark:border-white/10 pb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-500 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M8 2v4" />
                                <path d="M16 2v4" />
                                <rect width="18" height="18" x="3" y="4" rx="2" />
                                <path d="M3 10h18" />
                                <path d="m9 16 2 2 4-4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base sm:text-xl font-black text-foreground tracking-tight leading-none">Unit
                                Akan Disewa</h2>
                            <p class="text-[9px] sm:text-xs text-muted-foreground mt-1">Reservasi yang akan dimulai
                                segera.</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 px-3 sm:px-4 py-1.5 sm:py-2 bg-amber-500/10 border border-amber-500/20 rounded-full shrink-0">
                        <span
                            class="text-[9px] sm:text-[11px] font-black text-amber-600 dark:text-amber-400 tracking-wider">{{ $upcomingRentals->count() }}
                            Antrean</span>
                    </div>
                </div>
                @if($upcomingRentals->count() > 0)
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($upcomingRentals as $rental)
                            <div x-data="{ expanded: false }"
                                class="bg-card border border-border rounded-2xl overflow-hidden transition-all duration-300 hover:border-amber-500/30"
                                :class="expanded ? 'shadow-2xl ring-1 ring-amber-500/20' : 'shadow-sm'">

                                <!-- Accordion Header -->
                                <div @click="expanded = !expanded"
                                    class="p-2 sm:p-3 md:p-4 flex items-center justify-between gap-2 cursor-pointer bg-background hover:bg-muted/5 transition-colors">
                                    <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                                        <div class="flex -space-x-3 overflow-hidden shrink-0">
                                            @foreach($rental->units as $u)
                                                <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-lg bg-muted border border-background flex items-center justify-center shadow-sm"
                                                    title="{{ $u->seri }}">
                                                    <span
                                                        class="text-[7px] font-bold text-muted-foreground leading-none text-center px-0.5">
                                                        {{ substr($u->seri, 0, 3) }}<br>{{ substr($u->seri, -2) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <h3
                                                    class="font-bold text-amber-600 dark:text-amber-400 text-[10px] sm:text-sm truncate tracking-tight">
                                                    {{ $rental->units->pluck('seri')->join(', ') }}</h3>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span
                                                    class="text-[10px] sm:text-sm font-bold text-foreground truncate">{{ explode(' ', trim($rental->nama))[0] }}</span>
                                                <span class="hidden sm:inline-block h-1 w-1 rounded-full bg-border"></span>
                                                <span class="text-[10px] sm:text-xs text-muted-foreground truncate">
                                                    {{ $rental->waktu_mulai->format('d M, H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 sm:gap-6">
                                        <!-- Timeleft Column -->
                                        <div class="text-right w-24 sm:w-36 shrink-0 pr-2 sm:pr-4 border-r border-border/50"
                                            x-data="{ 
                                                            timeleft: '',
                                                            startTime: {{ $rental->waktu_mulai->timestamp }},
                                                            update() {
                                                                const now = Math.floor(Date.now() / 1000);
                                                                const diff = this.startTime - now;
                                                                if (diff <= 0) { this.timeleft = 'Dimulai'; return; }
                                                                const d = Math.floor(diff / 86400);
                                                                const h = Math.floor((diff % 86400) / 3600);
                                                                const m = Math.floor((diff % 3600) / 60);
                                                                this.timeleft = d > 0 ? `${d}h ${h}j` : `${h}j ${m}m`;
                                                            }
                                                        }" x-init="update(); setInterval(() => update(), 60000)">
                                            <p
                                                class="text-[7px] sm:text-[8px] font-bold text-muted-foreground tracking-widest leading-none mb-1">
                                                Mulai</p>
                                            <p x-text="timeleft"
                                                class="text-sm sm:text-xl font-bold text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                                            </p>
                                        </div>
                                        <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-muted/30 border border-border flex items-center justify-center text-muted-foreground transition-transform duration-300"
                                            :class="expanded ? 'rotate-180 bg-amber-500/10 text-amber-500 border-amber-500/20' : ''">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="expanded" x-collapse class="bg-muted/20 border-t border-border">
                                    <div class="p-3 md:p-4 grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                                        {{-- Kolom 1: Data Diri --}}
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <p class="text-sm font-bold text-white leading-tight">
                                                    {{ $rental->nama }}</p>
                                                @if($rental->sosial_media)
                                                    <span class="text-[10px] font-medium text-sky-400/60 transition-colors hover:text-sky-400 cursor-default">@ {{ $rental->sosial_media }}</span>
                                                @endif
                                                @if($rental->status === 'paid')
                                                    <x-ui.badge variant="blue" class="text-[9px] uppercase tracking-wider">Paid</x-ui.badge>
                                                @else
                                                    <x-ui.badge variant="amber" class="text-[9px] uppercase tracking-wider">Pending</x-ui.badge>
                                                @endif
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-[9px] font-bold text-white/40 uppercase leading-none tracking-wider">NIK / Identitas</p>
                                                    <p class="text-xs font-medium text-white/90 mt-1.5">{{ $rental->nik }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-bold text-white/40 uppercase leading-none tracking-wider">Booking Code</p>
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center rounded border bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200/50 dark:border-sky-900/50 px-2 py-0.5 font-mono text-[10px] font-bold uppercase tracking-tight">
                                                            {{ $rental->booking_code }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-white/40 uppercase leading-none tracking-wider">Alamat Lengkap</p>
                                                <p class="text-xs font-medium text-white/90 leading-tight mt-1.5">
                                                    {{ $rental->alamat ?: '-' }}</p>
                                            </div>
                                        </div>

                                        {{-- Kolom 2: Jadwal --}}
                                        <div class="space-y-4">
                                            <h4 class="text-[10px] font-bold text-white/40 tracking-widest uppercase mb-1">Jadwal Sewa</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="flex items-stretch gap-2.5">
                                                    <div class="w-1 bg-sky-500 rounded-full shrink-0 my-0.5 shadow-[0_0_8px_rgba(14,165,233,0.4)]"></div>
                                                    <div class="flex flex-col justify-center">
                                                        <p class="text-[9px] font-bold text-white/30 uppercase leading-none mb-1.5">Mulai</p>
                                                        <p class="text-[11px] font-bold text-white leading-tight">
                                                            {{ $rental->waktu_mulai->translatedFormat('d M, H:i') }}<br>
                                                            <span class="text-[10px] font-semibold text-sky-400 uppercase tracking-tighter">{{ $rental->waktu_mulai->translatedFormat('l') }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-stretch gap-2.5">
                                                    <div class="w-1 bg-emerald-500 rounded-full shrink-0 my-0.5 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></div>
                                                    <div class="flex flex-col justify-center">
                                                        <p class="text-[9px] font-bold text-white/30 uppercase leading-none mb-1.5">Selesai</p>
                                                        <p class="text-[11px] font-bold text-white leading-tight">
                                                            {{ $rental->waktu_selesai->translatedFormat('d M, H:i') }}<br>
                                                            <span class="text-[10px] font-semibold text-emerald-400 uppercase tracking-tighter">{{ $rental->waktu_selesai->translatedFormat('l') }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Kolom 3: Aksi --}}
                                        <div class="space-y-4 text-right flex flex-col h-full justify-end">
                                             @if($rental->status === 'paid')
                                                <div class="flex flex-row gap-2">
                                                    <button wire:click="openDendaModal({{ $rental->id }})" class="flex-1 py-1.5 rounded-lg bg-blue-500/10 text-blue-600 border border-blue-500/20 text-[9px] font-black hover:bg-blue-500 hover:text-white transition-all uppercase tracking-tighter">Selesaikan</button>
                                                    <button wire:confirm="Batalkan pesanan ini?" wire:click="cancel({{ $rental->id }})" class="flex-1 py-1.5 rounded-lg bg-rose-500/10 text-rose-600 border border-rose-500/20 text-[9px] font-black hover:bg-rose-500 hover:text-white transition-all uppercase tracking-tighter">Batal</button>
                                                </div>
                                            @else
                                                <div class="flex flex-row gap-2">
                                                    <button wire:confirm="Yakin ingin validasi pembayaran?" wire:click="markAsPaid({{ $rental->id }})" class="flex-1 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 text-[9px] font-black hover:bg-emerald-500 hover:text-white transition-all active:scale-95 uppercase tracking-tighter">Validasi</button>
                                                    <button wire:confirm="Batalkan pesanan ini?" wire:click="cancel({{ $rental->id }})" class="flex-1 py-1.5 rounded-lg bg-rose-500/10 text-rose-600 border border-rose-500/20 text-[9px] font-black hover:bg-rose-500 hover:text-white transition-all uppercase tracking-tighter">Batal</button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                    </div>
                @else
                    <div
                        class="py-12 bg-muted/5 border border-border border-dashed rounded-3xl flex flex-col items-center text-center">
                        <p class="text-[10px] font-bold text-muted-foreground opacity-50 tracking-widest">Tidak Ada
                            Reservasi Mendatang</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- Inspect Detail Modal -->
    <div x-show="modalOpen"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>

        <div class="fixed inset-0" @click="modalOpen = false"></div>

        <div class="relative bg-background border border-border shadow-2xl rounded-2xl w-full max-w-xl overflow-hidden animate-in zoom-in duration-200"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">

            @if($this->selectedRental)
                @php $r = $this->selectedRental; @endphp
                <!-- Header -->
                <div class="p-6 border-b border-border flex justify-between items-start bg-muted/5">
                    <div>
                        <h3 class="text-lg font-bold  text-foreground">Detail Transaksi</h3>
                        <p class="text-[11px] text-muted-foreground mt-0.5 italic opacity-70 uppercase">
                            {{ $r->booking_code }} • {{ $r->created_at->format('d M Y') }}</p>
                    </div>
                    <button @click="modalOpen = false"
                        class="rounded-md p-1.5 hover:bg-muted transition-colors border border-border bg-background">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            class="text-muted-foreground">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[75vh] space-y-8 scrollbar-hide">
                    <!-- Status Section -->
                    <div class="flex items-center justify-between p-4 bg-muted/30 rounded-lg border border-border">
                        <div class="space-y-1">
                            <p class="text-[9px] font-bold text-muted-foreground">Status Transaksi</p>
                            <div class="flex items-center gap-2">
                                @if($r->status === 'pending') <span
                                    class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span> <span
                                    class="text-xs font-semibold text-amber-600 dark:text-amber-400">Menunggu
                                    Pembayaran</span>
                                @elseif($r->status === 'paid') <span class="h-2 w-2 rounded-full bg-blue-500"></span> <span
                                    class="text-xs font-semibold text-blue-600 dark:text-blue-400">Dibayar (Aktif)</span>
                                @elseif($r->status === 'completed') <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                    <span class="text-xs font-semibold text-green-600 dark:text-green-400">Selesai</span>
                                @else <span class="h-2 w-2 rounded-full bg-red-500"></span> <span
                                    class="text-xs font-semibold text-red-600 dark:text-red-400">Dibatalkan</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right space-y-1 border-l border-border pl-4">
                            <p class="text-[9px] font-bold text-muted-foreground">Booking Code</p>
                            <p class="text-xs font-black text-primary">{{ $r->booking_code }}</p>
                        </div>
                    </div>

                    <!-- Customer & Unit Grid -->
                    <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Customer -->
                        <div class="space-y-4">
                            <h4 class="text-[9px] font-bold text-muted-foreground/70">Informasi Penyewa</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[9px] font-bold text-muted-foreground mb-0.5">Nama Lengkap</p>
                                    <p class="text-sm font-semibold text-foreground leading-tight">{{ $r->nama }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-muted-foreground mb-0.5">Kontak WhatsApp</p>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $r->no_wa) }}" target="_blank"
                                        class="text-sm font-bold text-primary hover:underline italic">{{ $r->no_wa }}</a>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-muted-foreground mb-0.5">Identitas (NIK)</p>
                                    <p class="text-sm font-medium text-foreground ">{{ $r->nik }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Rental Info -->
                        <div class="space-y-4">
                            <h4 class="text-[9px] font-bold text-muted-foreground/70">Detail Unit & Waktu</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[9px] font-bold text-muted-foreground mb-0.5">Unit Yang Disewa</p>
                                    <div class="space-y-2 mt-1">
                                        @foreach($r->units as $u)
                                            <div class="p-2 rounded bg-muted/50 border border-border/50">
                                                <p class="text-sm font-bold text-foreground">{{ $u->seri }}</p>
                                                <p class="text-[9px] text-muted-foreground">{{ $u->warna }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-muted-foreground mb-0.5">Jadwal Mulai</p>
                                    <p class="text-sm font-semibold text-foreground">
                                        {{ $r->waktu_mulai->format('d M Y, H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-muted-foreground mb-0.5">Jadwal Selesai</p>
                                    <p class="text-sm font-semibold text-foreground">
                                        {{ $r->waktu_selesai->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h4 class="text-[9px] font-bold text-muted-foreground/70">Ikhtisar Pembayaran</h4>
                        <div class="rounded-lg border border-border overflow-hidden">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-border">
                                    <tr class="bg-muted/10">
                                        <td class="py-2.5 px-4 text-muted-foreground text-xs">Harga Dasar Sewa</td>
                                        <td class="py-2.5 px-4 text-right font-medium text-xs">Rp
                                            {{ number_format($r->subtotal_harga, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($r->potongan_diskon > 0)
                                        <tr>
                                            <td class="py-2.5 px-4 text-red-500 font-medium text-xs">Potongan Diskon / Promo
                                            </td>
                                            <td class="py-2.5 px-4 text-right font-medium text-red-500 text-xs">- Rp
                                                {{ number_format($r->potongan_diskon, 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="py-2.5 px-4 text-muted-foreground text-xs">Kode Unik / Service Fee</td>
                                        <td class="py-2.5 px-4 text-right font-medium text-xs">+ Rp
                                            {{ $r->kode_unik_pembayaran }}</td>
                                    </tr>
                                    @if($r->denda > 0 || $r->denda_kerusakan > 0)
                                        <tr class="bg-amber-500/5">
                                            <td class="py-2.5 px-4 text-amber-600 font-bold text-xs">Total Denda</td>
                                            <td class="py-2.5 px-4 text-right font-bold text-amber-600 text-xs">+ Rp
                                                {{ number_format($r->denda + $r->denda_kerusakan, 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                    <tr class="bg-primary/5">
                                        <td class="py-4 px-4 font-bold text-foreground">TOTAL AKHIR</td>
                                        <td class="py-4 px-4 text-right text-lg font-black text-primary">Rp
                                            {{ number_format($r->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-border flex justify-end gap-3 bg-muted/5">
                    <a href="{{ route('admin.transactions') }}?search={{ $r->booking_code }}" wire:navigate
                        class="h-10 px-4 rounded-md border border-input bg-background flex items-center justify-center text-xs font-semibold hover:bg-muted transition-all">Riwayat
                        Transaksi</a>
                    <button @click="modalOpen = false"
                        class="h-10 px-8 rounded-md bg-primary text-primary-foreground shadow shadow-primary/20 flex items-center justify-center text-sm font-semibold hover:bg-primary/90 transition-all">Selesai
                        Meninjau</button>
                </div>
            @else
                <div class="p-24 text-center">
                    <div
                        class="animate-spin h-10 w-10 border-4 border-primary border-t-transparent rounded-full mx-auto mb-6">
                    </div>
                    <p class="text-[9px] font-bold text-muted-foreground opacity-80 italic animate-pulse">Menghubungkan
                        Server...</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Completion / Denda Modal -->
    <div x-show="$wire.completingTrxId" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak>
        
        <div class="relative bg-background border border-border shadow-2xl rounded-2xl w-full max-w-sm overflow-hidden animate-in zoom-in duration-200">
            <div class="p-6 border-b border-border bg-muted/5">
                <h3 class="text-base font-black text-foreground uppercase tracking-tight">Selesaikan Sewa</h3>
                <p class="text-[10px] text-muted-foreground mt-1">Konfirmasi pengembalian unit & cek denda.</p>
            </div>
            
            <div class="p-6 space-y-5">
                <!-- Info Telat -->
                <div class="p-3 rounded-xl {{ str_contains($lateDurationText, 'Tidak telat') ? 'bg-emerald-500/5 border border-emerald-500/10' : 'bg-rose-500/5 border border-rose-500/10' }}">
                    <p class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest">Durasi Keterlambatan</p>
                    <p class="text-sm font-black {{ str_contains($lateDurationText, 'Tidak telat') ? 'text-emerald-600' : 'text-rose-600' }} mt-1">{{ $lateDurationText }}</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2">Denda Keterlambatan (Rp)</label>
                        <input type="number" wire:model.live="dendaAmount" class="w-full h-11 px-4 bg-muted border border-border rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2">Denda Kerusakan (Rp)</label>
                        <input type="number" wire:model.live="dendaKerusakanAmount" class="w-full h-11 px-4 bg-muted border border-border rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2">Catatan Kondisi Barang</label>
                        <textarea wire:model="catatanKerusakan" placeholder="Misal: lecet dikit, layar gores..." class="w-full p-4 bg-muted border border-border rounded-xl text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all min-h-[80px]"></textarea>
                    </div>
                    
                    @if($dendaAmount > 0 || $dendaKerusakanAmount > 0)
                    <div>
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2">Metode Bayar Denda</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button @click="$wire.set('dendaMethod', 'cash')" class="py-2.5 rounded-xl border-2 text-[10px] font-black transition-all {{ $dendaMethod === 'cash' ? 'border-primary bg-primary/5 text-primary' : 'border-border bg-muted/5 text-muted-foreground' }}">TUNAI / CASH</button>
                            <button @click="$wire.set('dendaMethod', 'qris')" class="py-2.5 rounded-xl border-2 text-[10px] font-black transition-all {{ $dendaMethod === 'qris' ? 'border-primary bg-primary/5 text-primary' : 'border-border bg-muted/5 text-muted-foreground' }}">QRIS / TRANSFER</button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="p-6 bg-muted/5 border-t border-border flex gap-3">
                <button @click="$wire.closeDendaModal()" class="flex-1 py-3 text-[11px] font-black text-muted-foreground hover:bg-muted rounded-xl transition-all uppercase tracking-widest">Batal</button>
                <button wire:click="confirmDenda" class="flex-1 py-3 bg-primary text-primary-foreground text-[11px] font-black rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest">Selesaikan</button>
            </div>
        </div>
    </div>


</div>