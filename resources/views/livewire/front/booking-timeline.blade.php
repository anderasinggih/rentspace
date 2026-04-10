<div class="py-12 px-4 sm:px-6 lg:px-8 bg-muted/20 min-h-[calc(100vh-4rem)]">
    
    <div class="max-w-7xl mx-auto flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-background p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-foreground">Timeline Ketersediaan</h1>
                <p class="text-muted-foreground mt-1 text-sm">Lihat kapan daftar iPhone kami kosong dan siap disewa. (Skala 7 Hari)</p>
            </div>
            <a href="{{ route('public.booking') }}" wire:navigate class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-primary text-primary-foreground shadow-md hover:bg-primary/90 h-10 px-6 py-2">
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
                <div class="min-w-[900px] relative">
                    <!-- HEADER DATES -->
                    <div class="flex border-b border-border bg-muted/30">
                        <div class="w-48 shrink-0 p-4 font-semibold text-sm border-r border-border border-dashed flex items-center justify-center tracking-wide uppercase text-muted-foreground">Unit Tersedia</div>
                        <div class="flex-1 relative">
                            <div class="flex w-full h-full divide-x divide-border/50 divide-dashed">
                                @foreach($dates as $date)
                                <div class="flex-1 p-2 text-center {{ $date->isToday() ? 'bg-primary/5' : '' }}">
                                    <div class="text-sm font-bold text-foreground {{ $date->isToday() ? 'text-primary' : '' }}">{{ $date->format('d/m') }}</div>
                                    <div class="text-[10px] text-muted-foreground uppercase font-medium">{{ \Carbon\Carbon::parse($date)->translatedFormat('l') }}</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- BODY ROWS -->
                    <div class="flex flex-col relative divide-y divide-border/50">
                        @foreach($units as $unit)
                        <div class="flex h-20 group hover:bg-muted/10 transition-colors">
                            <!-- Unit Details Fixed width -->
                            <div class="w-48 shrink-0 p-4 border-r border-border border-dashed flex items-center gap-3 bg-background z-10 transition-colors group-hover:bg-muted/30">
                                <div class="w-1.5 h-10 rounded-full bg-primary/40"></div>
                                <div>
                                    <div class="font-bold text-sm tracking-tight">{{ $unit->seri }}</div>
                                    <div class="text-[11px] text-muted-foreground flex gap-1.5 items-center mt-0.5">
                                        <div class="w-2 h-2 rounded-full" style="background-color: {{ strtolower($unit->warna) == 'hitam' ? '#222' : (strtolower($unit->warna) == 'putih' ? '#eee' : 'orange') }}"></div>
                                        {{ $unit->warna }} &bull; {{ $unit->memori }}
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline Track relative -->
                            <div class="flex-1 relative bg-grid-pattern overflow-hidden">
                                <!-- Day Grid vertical borders -->
                                <div class="absolute inset-0 flex divide-x divide-border/50 divide-dashed pointer-events-none">
                                    @foreach($dates as $index => $date)
                                    <div class="flex-1 h-full {{ $date->isToday() ? 'bg-primary/[0.02]' : '' }}"></div>
                                    @endforeach
                                </div>

                                <!-- Actual Bookings Blocks -->
                                @foreach($unit->rentals as $rental)
                                    @php
                                        // Bound logic so block doesn't flow outside the 7 day visual constraints
                                        $rentStart = \Carbon\Carbon::parse($rental->waktu_mulai);
                                        $rentEnd = \Carbon\Carbon::parse($rental->waktu_selesai);
                                        
                                        $drawStart = $rentStart < $startDate ? $startDate : $rentStart;
                                        $drawEnd = $rentEnd > $endDate ? $endDate : $rentEnd;
                                        
                                        $totalHoursScope = $totalDays * 24;
                                        $offsetHours = $startDate->diffInHours($drawStart);
                                        
                                        // Use float for precise width scaling
                                        $durationHours = $drawStart->diffInMinutes($drawEnd) / 60; 
                                        if($durationHours < 1) $durationHours = 1; // min 1hr visual
                                        
                                        $leftPct = ($offsetHours / $totalHoursScope) * 100;
                                        $widthPct = ($durationHours / $totalHoursScope) * 100;

                                        // Status aesthetics
                                        $isPending = $rental->status == 'pending';
                                        $bgColor = $isPending ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300';
                                        $icon = $isPending ? '⏳' : '🔒';
                                    @endphp
                                    <div class="absolute top-3 bottom-3 rounded-lg shadow-sm border px-2 py-1 overflow-hidden transition-all hover:opacity-90 hover:scale-y-[1.02] cursor-default flex flex-col justify-center"
                                         style="left: {{ $leftPct }}%; width: {{ $widthPct }}%; z-index: 5;"
                                         title="{{ $rental->nama }} ({{ $rentStart->format('d/m H:i') }} - {{ $rentEnd->format('d/m H:i') }})"
                                    >
                                         <div class="flex items-center gap-1 w-full opacity-100">
                                             <span class="text-[10px]">{{ $icon }}</span>
                                             <span class="text-[10px] font-bold truncate leading-tight w-full">{{ $isPending ? 'Menunggu Bayar' : 'Disewa' }}</span>
                                         </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-6 justify-center text-xs text-muted-foreground">
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></div> Telah Disewa</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></div> Menunggu Pembayaran</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded border border-dashed border-border"></div> Tersedia</div>
            </div>
        @endif
    </div>
</div>
