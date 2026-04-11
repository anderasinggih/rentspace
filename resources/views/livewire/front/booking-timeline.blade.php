<div class="py-12 px-4 sm:px-6 lg:px-8 bg-background min-h-[calc(100vh-4rem)]">

    <div class="max-w-7xl mx-auto flex flex-col gap-6">
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-background p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-foreground">Timeline Ketersediaan</h1>
                <p class="text-muted-foreground mt-1 text-sm">Lihat kapan daftar iPhone kami kosong dan siap disewa.
                    (Skala 30 Hari)</p>
            </div>
            <a href="{{ route('public.booking') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground shadow-md hover:bg-primary/90 h-10 px-6 py-2">
                Sewa Sekarang
            </a>
        </div>

        @if($units->isEmpty())
        <div class="p-8 text-center bg-background rounded-2xl border border-border">
            <p class="text-muted-foreground">Belum ada unit yang tersedia.</p>
        </div>
        @else
        <!-- GANTT TIMELINE CHART -->
        <div class="overflow-x-auto hide-scrollbar pb-6 rounded-2xl border border-border bg-background shadow-sm">
            <div class="min-w-[900px] md:min-w-[2800px] relative">
                <!-- HEADER DATES -->
                <div class="flex border-b border-border bg-muted/30">
                    <div
                        class="w-24 md:w-48 shrink-0 p-2 md:p-4 font-semibold text-[10px] md:text-sm border-r border-border border-dashed flex items-center justify-center tracking-wide uppercase text-muted-foreground text-center">
                        Unit Tersedia</div>
                    <div class="flex-1 relative">
                        <div class="flex w-full h-full divide-x divide-border/50 divide-dashed">
                            @foreach($dates as $date)
                            <div class="flex-1 p-1 md:p-2 text-center {{ $date->isToday() ? 'bg-primary/5' : '' }}">
                                <div
                                    class="text-[10px] md:text-sm font-bold text-foreground {{ $date->isToday() ? 'text-primary' : '' }}">
                                    {{ $date->format('d/m') }}</div>
                                <div class="text-[8px] md:text-[10px] text-muted-foreground uppercase font-medium">{{
                                    \Carbon\Carbon::parse($date)->translatedFormat('D') }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- BODY ROWS -->
                <div class="flex flex-col relative divide-y divide-border/50">
                    @foreach($units as $unit)
                    <div class="flex h-14 md:h-20 group hover:bg-muted/10 transition-colors">
                        <!-- Unit Details Fixed width -->
                        <div
                            class="w-24 md:w-48 shrink-0 p-2 md:p-4 border-r border-border border-dashed flex items-center gap-2 md:gap-3 bg-background z-10 transition-colors group-hover:bg-muted/30">
                            <div class="w-1 md:w-1.5 h-8 md:h-10 rounded-full bg-primary/40 hidden md:block"></div>
                            <div class="w-full">
                                <div class="font-bold text-[11px] md:text-sm tracking-tight leading-tight truncate md:whitespace-normal">{{ $unit->seri }}</div>
                                <div class="text-[9px] md:text-[11px] text-muted-foreground flex flex-col md:flex-row md:gap-1.5 md:items-center mt-0.5">
                                    <div class="w-2 h-2 rounded-full hidden md:block"
                                        style="background-color: {{ strtolower($unit->warna) == 'hitam' ? '#222' : (strtolower($unit->warna) == 'putih' ? '#eee' : 'orange') }}">
                                    </div>
                                    <span class="md:hidden">{{ $unit->memori }}</span>
                                    <span class="hidden md:inline">{{ $unit->warna }} &bull; {{ $unit->memori }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Track relative -->
                        <div class="flex-1 relative bg-grid-pattern overflow-hidden">
                            <!-- Day Grid vertical borders -->
                            <div
                                class="absolute inset-0 flex divide-x divide-border/50 divide-dashed pointer-events-none">
                                @foreach($dates as $index => $date)
                                <div class="flex-1 h-full {{ $date->isToday() ? 'bg-primary/[0.02]' : '' }}"></div>
                                @endforeach
                            </div>

                            <!-- Actual Bookings Blocks -->
                            @foreach($unit->rentals as $rental)
                            @php
                            /* Bound logic so block doesn't flow outside the visual constraints */
                            $rentStart = \Carbon\Carbon::parse($rental->waktu_mulai);
                            $rentEnd = \Carbon\Carbon::parse($rental->waktu_selesai);

                            $drawStart = $rentStart < $startDate ? $startDate : $rentStart; $drawEnd=$rentEnd> $endDate
                                ? $endDate : $rentEnd;

                                $totalHoursScope = $totalDays * 24;
                                $offsetHours = $startDate->diffInHours($drawStart);

                                /* Use float for precise width scaling */
                                $durationHours = $drawStart->diffInMinutes($drawEnd) / 60;
                                if($durationHours < 1) { $durationHours=1; } $leftPct=($offsetHours / $totalHoursScope)
                                    * 100; $widthPct=($durationHours / $totalHoursScope) * 100; /* Status aesthetics */
                                    $isPending=$rental->status == 'pending';
                                    $isPaid = $rental->status == 'paid';

                                    if ($isPending) {
                                    $bgColor = 'bg-amber-500/20 text-amber-700 dark:text-amber-400 border
                                    border-amber-500/30';
                                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>';
                                    $label = 'Menunggu';
                                    } elseif ($isPaid) {
                                    $bgColor = 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border
                                    border-emerald-500/30';
                                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                        <path d="m9 11 3 3L22 4" />
                                    </svg>';
                                    $label = 'Disewa';
                                    } else {
                                    $bgColor = 'bg-slate-500/20 text-slate-700 dark:text-slate-400 border
                                    border-slate-500/30';
                                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                                        <line x1="4" x2="4" y1="22" y2="15" />
                                    </svg>';
                                    $label = 'Selesai';
                                    }
                                    @endphp
                                    <div class="absolute top-1 bottom-1 md:top-2 md:bottom-2 rounded-sm md:rounded-md {{ $bgColor }} px-1 md:px-2 py-0.5 overflow-hidden transition-all hover:bg-opacity-80 hover:scale-y-[1.02] cursor-default flex flex-col justify-center"
                                        style="left: {{ $leftPct }}%; width: {{ $widthPct }}%; z-index: 5;"
                                        title="{{ $rental->nama }} ({{ $rentStart->format('d/m H:i') }} - {{ $rentEnd->format('d/m H:i') }})">
                                        <div class="flex items-center gap-1 md:gap-1.5 w-full">
                                            <span class="shrink-0 flex items-center justify-center scale-75 md:scale-100">{!! $icon !!}</span>
                                            <span
                                                class="text-[7px] md:text-[10px] font-semibold truncate leading-tight w-full tracking-tight">{{
                                                $label }}</span>
                                        </div>
                                    </div>
                                    @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center gap-8 justify-center text-xs font-medium text-muted-foreground mt-4">
            <div class="flex items-center gap-2">
                <div
                    class="w-4 h-4 rounded bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <path d="m9 11 3 3L22 4" />
                    </svg>
                </div> Telah Disewa
            </div>
            <div class="flex items-center gap-2">
                <div
                    class="w-4 h-4 rounded bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-700 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div> Menunggu Pembayaran
            </div>
            <div class="flex items-center gap-2">
                <div
                    class="w-4 h-4 rounded bg-slate-500/20 border border-slate-500/30 flex items-center justify-center text-slate-700 dark:text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                        <line x1="4" x2="4" y1="22" y2="15" />
                    </svg>
                </div> Selesai
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded border border-dashed border-border bg-muted/20"></div> Tersedia
            </div>
        </div>
        @endif
    </div>
</div>