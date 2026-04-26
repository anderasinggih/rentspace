<div class="relative min-h-screen pb-20 overflow-hidden">
    <!-- Liquid Glass Background Elements -->
    <div class="fixed inset-0 pointer-events-none -z-10 bg-background/50">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-500/10 blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[10%] right-[-5%] w-[35%] h-[35%] rounded-full bg-primary/10 blur-[100px]" style="animation-delay: 2s;"></div>
        <div class="absolute top-[40%] left-[30%] w-[25%] h-[25%] rounded-full bg-emerald-500/5 blur-[80px]"></div>
    </div>

    <!-- Header Section -->
    <div class="relative mb-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3">
                    Dashboard
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-widest border border-primary/20 animate-pulse">Live</span>
                </h1>
                <p class="text-xs text-muted-foreground mt-1 font-medium opacity-70">Sistem Monitoring & Analitik RentSpace</p>
            </div>
            
            <div class="flex items-center gap-2 p-1.5 bg-muted/30 backdrop-blur-md rounded-2xl border border-white/5 shadow-sm">
                <select wire:model.live="preset"
                    class="h-9 w-full sm:w-[140px] rounded-xl border-none bg-background/80 shadow-inner px-3 py-1 text-xs font-bold transition-all focus:ring-2 focus:ring-primary/20 outline-none">
                    <option value="7">7 Hari Terakhir</option>
                    <option value="30">30 Hari Terakhir</option>
                    <option value="90">3 Bulan Terakhir</option>
                    <option value="all">Semua Waktu</option>
                    <option value="custom">Pilih Tanggal</option>
                </select>

                @if($preset === 'custom')
                    <div class="flex items-center gap-1.5 px-2">
                        <input type="date" wire:model.live="startDate" class="h-8 bg-transparent border-none text-[10px] p-0 font-bold outline-none">
                        <span class="text-muted-foreground opacity-30 tracking-tighter">→</span>
                        <input type="date" wire:model.live="endDate" class="h-8 bg-transparent border-none text-[10px] p-0 font-bold outline-none">
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Snapshot Metrics: Pure Shadcn Liquid Glass -->
    <div class="mb-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-4">
            <!-- Stats Card 1 -->
            <div class="group relative overflow-hidden rounded-2xl bg-card/60 backdrop-blur-xl border border-border/40 p-3 md:p-5 shadow-sm transition-all hover:border-primary/50 hover:shadow-xl hover:shadow-primary/5">
                <div class="absolute top-0 right-0 p-2 opacity-5 transition-transform group-hover:scale-125 group-hover:opacity-10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-4.5c.3-.8 1-1.5 1.8-1.5h7.6c.8 0 1.5.7 1.8 1.5L21 21"/><path d="M12 2v3"/><path d="m18.5 4.5-2.1 2.1"/><path d="m22 12-3 0"/><path d="m2 12 3 0"/><path d="m5.5 4.5 2.1 2.1"/></svg>
                </div>
                <p class="text-[9px] md:text-[10px] font-black uppercase text-muted-foreground tracking-widest mb-1">Unit Aktif</p>
                <div class="flex items-end gap-2">
                    <span class="text-xl md:text-3xl font-black text-foreground">{{ $activeUnits }}</span>
                    <span class="text-[10px] font-bold text-muted-foreground opacity-50 mb-1">/{{ $totalUnits }}</span>
                </div>
                <div class="mt-2 h-1 w-full bg-muted/50 rounded-full overflow-hidden">
                    <div class="h-full bg-primary" style="width: {{ $totalUnits > 0 ? ($activeUnits / $totalUnits) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Stats Card 2 -->
            <div class="group relative overflow-hidden rounded-2xl bg-card/60 backdrop-blur-xl border border-border/40 p-3 md:p-5 shadow-sm transition-all hover:border-amber-500/50 hover:shadow-xl hover:shadow-amber-500/5">
                <div class="absolute top-0 right-0 p-2 opacity-5 transition-transform group-hover:scale-125 group-hover:opacity-10 text-amber-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7"/><path d="M16 19h6"/><path d="M19 16v6"/><circle cx="12" cy="12" r="3"/><path d="M12 7v5l2 2"/></svg>
                </div>
                <p class="text-[9px] md:text-[10px] font-black uppercase text-muted-foreground tracking-widest mb-1">Pending</p>
                <div class="flex items-end gap-2">
                    <span class="text-xl md:text-3xl font-black text-amber-500">{{ $pendingRentals }}</span>
                    <span class="text-[10px] font-bold text-amber-500/50 mb-1">TRX</span>
                </div>
                <div class="mt-2 text-[9px] font-black tracking-tight text-amber-600 bg-amber-500/5 px-1.5 py-0.5 rounded-md inline-block">
                    Rp {{ number_format($pendingRevenue / 1000, 0, ',', '.') }}k
                </div>
            </div>

            <!-- Stats Card 3 -->
            <div class="group relative overflow-hidden rounded-2xl bg-card/60 backdrop-blur-xl border border-border/40 p-3 md:p-5 shadow-sm transition-all hover:border-emerald-500/50 hover:shadow-xl hover:shadow-emerald-500/5">
                <div class="absolute top-0 right-0 p-2 opacity-5 transition-transform group-hover:scale-125 group-hover:opacity-10 text-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                </div>
                <p class="text-[9px] md:text-[10px] font-black uppercase text-muted-foreground tracking-widest mb-1 text-emerald-600">Daily Rev</p>
                <div class="flex items-end gap-1">
                    <span class="text-base md:text-xl font-bold text-muted-foreground opacity-50 mb-1">Rp</span>
                    <span class="text-xl md:text-3xl font-black text-emerald-600">{{ number_format($todayRevenue / 1000, 0, ',', '.') }}k</span>
                </div>
                <div class="mt-2 flex items-center gap-1.5">
                    <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
                    <span class="text-[9px] font-bold text-emerald-600/70 uppercase">Real-time Goal</span>
                </div>
            </div>

            <!-- Stats Card 4 -->
            <div class="group relative overflow-hidden rounded-2xl bg-card/60 backdrop-blur-xl border border-border/40 p-3 md:p-5 shadow-sm transition-all hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/5">
                <div class="absolute top-0 right-0 p-2 opacity-5 transition-transform group-hover:scale-125 group-hover:opacity-10 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                </div>
                <p class="text-[9px] md:text-[10px] font-black uppercase text-muted-foreground tracking-widest mb-1 text-blue-600">Tersewa Today</p>
                <div class="flex items-end gap-2">
                    <span class="text-xl md:text-3xl font-black text-blue-600">{{ $todayRentals }}</span>
                    <span class="text-[10px] font-bold text-blue-500/50 mb-1">UNIT</span>
                </div>
                <p class="mt-2 text-[9px] text-blue-600/60 font-bold uppercase">{{ now()->format('l, d M') }}</p>
            </div>
        </div>
    </div>

    <!-- Period Summary Table: Tight & Complex for Mobile -->
    <div class="mb-8 overflow-hidden rounded-2xl border border-border/40 bg-card/30 backdrop-blur-sm">
        <div class="p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-widest text-foreground/70">Performa Periode Terpilih</h3>
            <div class="text-[10px] font-bold text-muted-foreground opacity-60 bg-black/10 px-2 py-0.5 rounded-full">High Density View</div>
        </div>
        
        @php
            function getGainDisplay($gain, $abs = null) {
                if ($gain === null) return 'N/A';
                $isPos = $gain >= 0;
                $color = $isPos ? 'text-emerald-500' : 'text-red-500';
                $arrow = $isPos ? '▲' : '▼';
                return "<span class='flex items-center gap-1 $color'><span class='text-[8px]'>$arrow</span> " . abs($gain) . "%</span>";
            }
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-white/5 border-b border-white/5">
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-muted-foreground uppercase opacity-60">Volume Sewa</span>
                <span class="text-lg font-black text-foreground">{{ $periodRentals }} <span class="text-[10px] text-muted-foreground font-normal">Order</span></span>
                <div class="text-[10px] font-bold">{!! getGainDisplay($gainRentals) !!}</div>
            </div>
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-muted-foreground uppercase opacity-60">Gross Revenue</span>
                <span class="text-lg font-black text-foreground">Rp {{ number_format($periodRevenue / 1000, 0, ',', '.') }}k</span>
                <div class="text-[10px] font-bold">{!! getGainDisplay($gainRevenue) !!}</div>
            </div>
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-muted-foreground uppercase opacity-60">Affiliate Comm.</span>
                <span class="text-lg font-black text-red-500">Rp {{ number_format($periodCommissions / 1000, 0, ',', '.') }}k</span>
                <span class="text-[9px] font-bold text-red-500/50 uppercase">Potongan Omset</span>
            </div>
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-muted-foreground uppercase opacity-60">Total Diskon</span>
                <span class="text-lg font-black text-foreground">Rp {{ number_format($periodDiscounts / 1000, 0, ',', '.') }}k</span>
                <span class="text-[9px] font-bold text-muted-foreground/50 uppercase">Keluar</span>
            </div>
        </div>

        <div class="bg-primary/5 p-3 flex items-center justify-between">
            <span class="text-[10px] font-black uppercase text-primary/70 tracking-widest">Net Revenue Estimate</span>
            <span class="text-sm md:text-base font-black text-primary">Rp {{ number_format($periodNetRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Charts Section: Two Column Layout on Desktop -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="rounded-3xl bg-card border border-border/40 shadow-sm overflow-hidden group">
            <div class="p-4 md:p-6 border-b border-white/5 flex items-center justify-between bg-gradient-to-r from-primary/5 to-transparent">
                <div>
                    <h3 class="text-sm font-bold text-foreground">Aliran Pendapatan</h3>
                    <p class="text-[10px] text-muted-foreground font-medium opacity-60">Grafik perbandingan kotor vs bersih</p>
                </div>
                <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary transition-transform group-hover:rotate-12">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="m13 13 9 9"/></svg>
                </div>
            </div>
            <div class="p-2 md:p-4">
                <div id="revenueChart" class="w-full h-[300px]" wire:ignore></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-1 gap-4">
            <!-- Transactions Trend -->
            <div class="rounded-3xl bg-card border border-border/40 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-widest text-foreground/70">Frekuensi Order</h3>
                    <div class="h-6 w-6 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20V16"/></svg>
                    </div>
                </div>
                <div class="p-2">
                    <div id="transactionsChart" class="w-full h-[140px]" wire:ignore></div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="rounded-3xl bg-card border border-border/40 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-white/5">
                    <h3 class="text-xs font-black uppercase tracking-widest text-foreground/70">Channel Pembayaran</h3>
                </div>
                <div class="p-2 flex items-center justify-center">
                    <div id="paymentDonutChart" class="w-full h-[180px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Panels: High Density Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8">
        <!-- Top Units -->
        <div class="rounded-3xl bg-card border border-border/40 shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-white/5 bg-emerald-500/5">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-foreground/80">Inventory Performance</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-muted/30 text-[9px] font-black uppercase tracking-tighter text-muted-foreground/60 border-b border-white/5">
                        <tr>
                            <th class="px-4 py-2">Unit iPhone</th>
                            <th class="px-2 py-2 text-center">Frek</th>
                            <th class="px-2 py-2 text-center">Dur</th>
                            <th class="px-4 py-2 text-right">Rev</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] divide-y divide-white/5">
                        @foreach($topUnits as $tu)
                            <tr class="hover:bg-primary/5 transition-colors group">
                                <td class="px-4 py-2.5 font-bold text-foreground">{{ $tu->unit ? $tu->unit->seri : 'Unknown' }}</td>
                                <td class="px-2 py-2.5 text-center font-medium opacity-60">{{ $tu->rent_count }}x</td>
                                <td class="px-2 py-2.5 text-center font-medium opacity-60">{{ $tu->hours }}h</td>
                                <td class="px-4 py-2.5 text-right font-black text-emerald-600">Rp {{ number_format($tu->revenue / 1000, 0, ',', '.') }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Tenants -->
        <div class="rounded-3xl bg-card border border-border/40 shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-white/5 bg-blue-500/5">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-foreground/80">Loyal Customers</h3>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-muted/30 text-[9px] font-black uppercase tracking-tighter text-muted-foreground/60 border-b border-white/5">
                        <tr>
                            <th class="px-4 py-2">Pelanggan</th>
                            <th class="px-2 py-2 text-center">TRX</th>
                            <th class="px-4 py-2 text-right">Spent</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] divide-y divide-white/5">
                        @foreach($topTenants as $tenant)
                            <tr class="hover:bg-primary/5 transition-colors">
                                <td class="px-4 py-2">
                                    <div class="font-bold text-foreground truncate max-w-[120px]">{{ $tenant->nama }}</div>
                                    <div class="text-[9px] text-muted-foreground opacity-50">{{ $tenant->no_wa }}</div>
                                </td>
                                <td class="px-2 py-2 text-center font-black opacity-60">{{ $tenant->total_rentals }}x</td>
                                <td class="px-4 py-2 text-right font-black text-primary">Rp{{ number_format($tenant->total_spent / 1000, 0, ',', '.') }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Now Section: Real-time Active Table -->
    <div class="rounded-3xl bg-card/60 backdrop-blur-xl border border-primary/20 shadow-2xl overflow-hidden">
        <div class="p-5 border-b border-white/5 flex items-center justify-between bg-primary/10">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="h-3 w-3 rounded-full bg-primary animate-ping opacity-75"></div>
                    <div class="absolute inset-0 h-3 w-3 rounded-full bg-primary"></div>
                </div>
                <h3 class="text-sm font-black uppercase tracking-widest text-primary">Aktif Di Tangan Pelanggan</h3>
            </div>
            <span class="text-[10px] font-bold text-primary/50 bg-primary/10 px-3 py-1 rounded-full border border-primary/20">Monitor Satuan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-muted/40 text-[9px] font-black uppercase tracking-widest text-muted-foreground/60">
                    <tr>
                        <th class="px-6 py-4">Unit iPhone / iPad</th>
                        <th class="px-6 py-4">Atas Nama</th>
                        <th class="px-6 py-4">Estimasi Selesai</th>
                        <th class="px-6 py-4 text-right">Progress</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] font-medium divide-y divide-white/5 bg-white/[0.02]">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalMinutes = abs(now()->diffInMinutes($end));
                            $h = floor($totalMinutes / 60);
                            $m = $totalMinutes % 60;
                            $diffText = ($h > 0 ? $h . 'j ' : '') . ($m > 0 ? $m . 'm' : ($h == 0 ? '0m' : ''));
                        @endphp
                        <tr class="hover:bg-white/[0.05] transition-all">
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-2 py-0.5 rounded-md bg-foreground/5 border border-foreground/10 font-bold text-foreground/80 tracking-tight">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-black text-foreground text-xs">{{ $rental->nama }}</div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[9px] font-bold text-primary/60 tracking-widest px-1.5 py-0.5 bg-primary/10 rounded border border-primary/20">{{ $rental->booking_code }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-foreground/80 tracking-tight">{{ $end->format('H:i') }} <span class="text-[9px] opacity-40">•</span> {{ $end->format('d M') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($diffInHours < 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-500 text-white text-[9px] font-black uppercase tracking-widest shadow-lg shadow-red-500/20">Telat Masuk</span>
                                @elseif($diffInHours < 3)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-500 text-black text-[9px] font-black uppercase tracking-widest shadow-lg shadow-amber-500/20">Sisa {{ $diffText }}</span>
                                @else
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-emerald-500 text-[10px] font-black uppercase tracking-tighter">Aman ({{ $h }} jam+)</span>
                                        <div class="h-1 w-20 bg-emerald-500/10 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 w-full animate-pulse"></div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center opacity-30 grayscale">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="18" x="8" y="3" rx="2"/><path d="M12 11h.01"/><path d="M12 15h.01"/></svg>
                                    <p class="text-[10px] font-black uppercase mt-4 tracking-widest text-muted-foreground">Belum ada unit yang disewa hari ini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@script
<script>
    if (typeof ApexCharts !== 'undefined') {
        const initCharts = () => {
            const chartDom = document.querySelector("#revenueChart");
            if (!chartDom) return;

            function getChartColors() {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    isDark,
                    textColor: isDark ? '#a1a1aa' : '#71717a',
                    borderColor: 'transparent',
                    tooltipTheme: isDark ? 'dark' : 'light',
                };
            }

            let colors = getChartColors();
            let revChart, trxChart, donutChart;

            const baseOptions = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                theme: { mode: colors.isDark ? 'dark' : 'light' },
                grid: { show: false, padding: { left: 0, right: 0 } },
                stroke: { width: 3, curve: 'smooth' }
            };

            // ── 1. REVENUE AREA CHART ──────────────────────────────────────────────
            revChart = new ApexCharts(document.querySelector("#revenueChart"), {
                ...baseOptions,
                series: [
                    { name: 'Omset Kotor', data: @json($chartRevenue) },
                    { name: 'Omset Bersih', data: @json($chartNetRevenue) }
                ],
                chart: { ...baseOptions.chart, type: 'area', height: 300 },
                colors: ['#6366f1', '#10b981'],
                xaxis: {
                    categories: @json($chartCategories),
                    axisBorder: { show: false }, axisTicks: { show: false },
                    labels: { style: { colors: colors.textColor, fontSize: '9px', fontWeight: 600 } },
                    tickAmount: window.innerWidth < 640 ? 3 : 8
                },
                yaxis: {
                    labels: {
                        formatter: (val) => val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val,
                        style: { colors: colors.textColor, fontSize: '10px' }
                    }
                },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 95] } },
                tooltip: { theme: colors.tooltipTheme, y: { formatter: (val) => "Rp " + val.toLocaleString("id-ID") } }
            });
            revChart.render();

            // ── 2. TRANSACTIONS BAR CHART ──────────────────────────────────────────
            trxChart = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...baseOptions,
                series: [{ name: 'Jml Order', data: @json($chartTransactions) }],
                chart: { ...baseOptions.chart, type: 'bar', height: 140 },
                colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '30%' } },
                xaxis: {
                    categories: @json($chartCategories),
                    axisBorder: { show: false }, axisTicks: { show: false },
                    labels: { show: false }
                },
                yaxis: { show: false },
                tooltip: { theme: colors.tooltipTheme }
            });
            trxChart.render();

            // ── 3. PAYMENT METHOD DONUT CHART ─────────────────────────────────────
            donutChart = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...baseOptions,
                series: @json($paymentCounts),
                labels: @json($paymentLabels),
                chart: { ...baseOptions.chart, type: 'donut', height: 200 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                dataLabels: { enabled: false },
                legend: { position: 'bottom', fontSize: '9px', fontWeight: 700, labels: { colors: colors.textColor } },
                plotOptions: { pie: { donut: { size: '75%', labels: { show: true, total: { show: true, color: colors.textColor, label: 'TOTAL' } } } } },
                stroke: { width: 0 }
            });
            donutChart.render();

            // ── Livewire data updates ──────────────────────────────────────────────
            Livewire.on('chartDataUpdated', (data) => {
                const d = Array.isArray(data) ? data[0] : data;
                revChart?.updateOptions({ xaxis: { categories: d.categories } });
                revChart?.updateSeries([{ name: 'Omset Kotor', data: d.revenue }, { name: 'Omset Bersih', data: d.netRevenue }]);
                trxChart?.updateOptions({ xaxis: { categories: d.categories } });
                trxChart?.updateSeries([{ name: 'Jml Order', data: d.transactions }]);
            });

            // ── Theme toggle observer ─────────────────────────────────────────────
            const observer = new MutationObserver(() => {
                const c = getChartColors();
                [revChart, trxChart, donutChart].forEach(chart => {
                    chart?.updateOptions({
                        theme: { mode: c.isDark ? 'dark' : 'light' },
                        tooltip: { theme: c.isDark ? 'dark' : 'light' },
                        xaxis: { labels: { style: { colors: c.textColor } } },
                        legend: { labels: { colors: c.textColor } }
                    });
                });
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            document.addEventListener('livewire:navigating', () => {
                observer.disconnect();
                [revChart, trxChart, donutChart].forEach(c => c?.destroy());
            }, { once: true });
        };

        initCharts();
    }
</script>
@endscript