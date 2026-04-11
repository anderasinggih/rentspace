<div class="pb-10">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-foreground">Dashboard Admin</h1>
        <p class="text-muted-foreground mt-1 text-xs md:text-sm">Ringkasan statistik, grafis pendapatan, dan
            analisis penyewaan.</p>
    </div>

    <!-- Snapshot Metrics -->
    <div>
        <h2 class="text-sm font-semibold text-foreground mb-3 border-b border-border pb-2">Status & Hari Ini (Real-time)
        </h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
            <div class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between">
                <h3 class="text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">Total & Aktif Unit
                </h3>
                <p class="text-xl md:text-2xl font-bold text-foreground">{{ $activeUnits }} <span
                        class="text-xs font-normal text-muted-foreground">/ {{ $totalUnits }} Unit</span></p>
            </div>
            <div class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between">
                <h3 class="text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">Antrean Pending
                </h3>
                <p class="text-xl md:text-2xl font-bold text-amber-500">{{ $pendingRentals }} <span
                        class="text-xs font-normal text-muted-foreground">Order</span></p>
            </div>
            <div
                class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between border-l-4 border-l-emerald-500">
                <h3 class="text-xs font-semibold text-emerald-600 mb-1 uppercase tracking-wider">Pendapatan Hari Ini
                </h3>
                <p class="text-xl md:text-2xl font-bold text-emerald-600">Rp {{ number_format($todayRevenue/1000, 0,
                    ',', '.') }}k</p>
            </div>
            <div
                class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between border-l-4 border-l-primary">
                <h3 class="text-xs font-semibold text-primary mb-1 uppercase tracking-wider">Sewa Hari Ini</h3>
                <p class="text-xl md:text-2xl font-bold text-primary">{{ $todayRentals }} <span
                        class="text-xs font-normal opacity-70">Transaksi</span></p>
            </div>
        </div>
    </div>

    <!-- Scoped Period Metrics -->
    <div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-3 border-b border-border pb-2">
            @if($preset === 'all')
            <h2 class="text-sm font-semibold text-foreground">Keseluruhan (Sepanjang Waktu)</h2>
            @else
            <h2 class="text-sm font-semibold text-foreground">Performa Periode Terpilih</h2>
            @endif
            
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                <select wire:model.live="preset"
                    class="h-8 w-full sm:w-[140px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="7">7 Hari Terakhir</option>
                    <option value="30">30 Hari Terakhir</option>
                    <option value="90">3 Bulan Terakhir</option>
                    <option value="all">Semua Waktu</option>
                    <option value="custom">Pilih Tanggal</option>
                </select>

                @if($preset === 'custom')
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" wire:model.live="startDate"
                        class="h-8 w-full sm:w-[120px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <span class="text-muted-foreground text-xs">-</span>
                    <input type="date" wire:model.live="endDate"
                        class="h-8 w-full sm:w-[120px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
            @php
                function gainBadge($gain, $abs = null) {
                    if ($gain === null) return '<span class="text-[10px] text-muted-foreground">vs periode lalu: -</span>';
                    $isPositive = $gain >= 0;
                    $color = $isPositive
                        ? 'bg-emerald-500 text-white border-emerald-500'
                        : 'bg-red-500 text-white border-red-500';
                    $arrow = $isPositive ? '▲' : '▼';
                    $absText = $abs !== null
                        ? ' (' . ($abs >= 0 ? '+' : '') . 'Rp ' . number_format(abs($abs)/1000, 0, ',', '.') . 'k)'
                        : '';
                    return '<span class="inline-flex items-center border rounded px-1.5 py-0.5 text-[10px] font-semibold ' . $color . '">'
                        . $arrow . ' ' . abs($gain) . '%' . $absText . '</span>';
                }
            @endphp
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Sewa (Periode)</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">{{ $periodRentals }} <span
                        class="text-xs font-normal text-muted-foreground">Order</span></p>
                <div class="mt-2">{!! gainBadge($gainRentals) !!}</div>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Omset (Periode)</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">Rp {{ number_format($periodRevenue, 0, ',', '.')
                    }}</p>
                <div class="mt-2">{!! gainBadge($gainRevenue, $gainAbsRevenue) !!}</div>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Diskon Keluar</h3>
                <p class="text-lg md:text-xl font-bold text-red-500">-Rp {{ number_format($periodDiscounts, 0, ',', '.')
                    }}</p>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Rata-rata Transaksi</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">Rp {{ $periodRentals > 0 ?
                    number_format($periodRevenue/$periodRentals, 0, ',', '.') : 0 }}</p>
            </div>
        </div>
    </div>

    <!-- Charts - Full Width Stacked -->
    <div class="flex flex-col gap-4 mb-6">
        <!-- Revenue Area Chart -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Tren Pendapatan</h2>
                <p class="text-xs text-muted-foreground mt-1">Pendapatan kotor selama periode yang dipilih</p>
            </div>
            <div class="p-3">
                <div id="revenueChart" class="w-full h-[260px]" wire:ignore></div>
            </div>
        </div>

        <!-- Transactions Bar Chart -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Tren Transaksi</h2>
                <p class="text-xs text-muted-foreground mt-1">Jumlah penyewaan selesai / lunas</p>
            </div>
            <div class="p-3">
                <div id="transactionsChart" class="w-full h-[260px]" wire:ignore></div>
            </div>
        </div>

        <!-- Payment Method Donut Chart -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Metode Pembayaran</h2>
                <p class="text-xs text-muted-foreground mt-1">Distribusi metode bayar periode ini</p>
            </div>
            <div class="p-3">
                <div id="paymentDonutChart" class="w-full h-[260px]" wire:ignore></div>
            </div>
        </div>
    </div>


    <!-- Dual Analysis Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 w-full">
        <!-- Analisis Per Unit -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Unit Paling Laris</h2>
                <p class="text-[11px] text-muted-foreground mt-1">Berdasarkan total pendapatan di periode terpilih</p>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-xs text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Seri iPhone</th>
                            <th class="px-4 py-2 font-medium text-center">Sewa</th>
                            <th class="px-4 py-2 font-medium text-center">Total Durasi</th>
                            <th class="px-4 py-2 font-medium text-right">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topUnits as $tu)
                        <tr class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium text-foreground flex items-center gap-2">
                                {{ $tu->unit ? $tu->unit->seri : 'Unit Terhapus' }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ $tu->rent_count }}x</td>
                            <td class="px-4 py-3 text-center">{{ $tu->hours }} Jam</td>
                            <td class="px-4 py-3 text-right font-medium text-emerald-600">Rp {{
                                number_format($tu->revenue/1000, 0, ',', '.') }}k</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">Belum ada data unit
                                tersewa.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Analisis Data Penyewa -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Penyewa Setia</h2>
                <p class="text-[11px] text-muted-foreground mt-1">Berdasarkan total belanja di periode terpilih</p>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-xs text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Pelanggan</th>
                            <th class="px-4 py-2 font-medium text-center">Sewa</th>
                            <th class="px-4 py-2 font-medium text-right">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topTenants as $tenant)
                        <tr class="hover:bg-muted/30">
                            <td class="px-4 py-2 text-foreground">
                                <div class="font-medium">{{ $tenant->nama }}</div>
                                <div class="text-[10px] text-muted-foreground">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $tenant->total_rentals }}x</td>
                            <td class="px-4 py-2 text-right font-medium text-emerald-600">Rp {{
                                number_format($tenant->total_spent/1000, 0, ',', '.') }}k</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-muted-foreground">Belum ada data penyewa
                                setia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Rentals Right Now -->
    <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm w-full">
        <div class="p-4 border-b border-border">
            <h2 class="text-sm font-semibold leading-none tracking-tight">Status Sewa Berjalan Berjalan</h2>
            <p class="text-[11px] text-muted-foreground mt-1">Daftar unit yang sedang beredar di tangan pelanggan saat
                ini</p>
        </div>
        <div class="w-full overflow-x-auto">
            <table class="w-full text-xs text-left whitespace-nowrap">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Unit</th>
                        <th class="px-4 py-2 font-medium">Penyewa</th>
                        <th class="px-4 py-2 font-medium">Waktu Selesai</th>
                        <th class="px-4 py-2 font-medium text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($activeRentals as $rental)
                    @php
                    $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                    $diff = now()->diffInHours($end, false);
                    @endphp
                    <tr class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ $rental->unit ? $rental->unit->seri :
                            'Terhapus' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-foreground">{{ $rental->nama }}</span><br>
                            <span class="text-[10px] text-muted-foreground">{{ $rental->no_wa }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $end->format('d M, H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($diff < 0) <span
                                class="inline-flex px-1.5 py-0.5 rounded-sm text-[10px] font-bold bg-destructive/10 text-destructive">
                                Telat Masuk
                                </span>
                                @elseif($diff < 3) <span
                                    class="inline-flex px-1.5 py-0.5 rounded-sm text-[10px] font-bold bg-amber-500/10 text-amber-600">
                                    Sisa {{ $diff }} Jam
                                    </span>
                                    @else
                                    <span
                                        class="inline-flex px-1.5 py-0.5 rounded-sm text-[10px] font-bold bg-emerald-500/10 text-emerald-600">
                                        Aman
                                    </span>
                                    @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">Tidak ada jadwal penyewaan
                            yang sedang aktif dibawa pelanggan saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        if (typeof ApexCharts === 'undefined') return;

        function getChartColors() {
            const isDark = document.documentElement.classList.contains('dark');
            const style = getComputedStyle(document.documentElement);
            const resolve = (v) => `hsl(${style.getPropertyValue(v).trim()})`;
            return {
                isDark,
                textColor:   isDark ? '#a1a1aa' : '#71717a',
                borderColor: isDark ? '#27272a' : '#e4e4e7',
                tooltipTheme: isDark ? 'dark' : 'light',
            };
        }

        let colors = getChartColors();

        // ── 1. REVENUE AREA CHART ──────────────────────────────────────────────
        var revChart = new ApexCharts(document.querySelector("#revenueChart"), {
            series: [{ name: 'Pendapatan', data: @json($chartRevenue) }],
            chart: { type: 'area', height: 220, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent', offsetX: -10, offsetY: 10 },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2, colors: ['#6366f1'] },
            xaxis: {
                categories: @json($chartCategories),
                tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false },
                labels: { style: { colors: colors.textColor, fontFamily: 'inherit', fontSize: '10px' } },
                tickAmount: 8
            },
            yaxis: {
                labels: {
                    formatter: (val) => {
                        if (val >= 1000000) return (val / 1000000).toFixed(1).replace('.', ',') + ' jt';
                        if (val >= 1000) return (val / 1000).toFixed(0) + ' rb';
                        return val;
                    },
                    style: { colors: colors.textColor, fontFamily: 'inherit', fontSize: '10px' }
                }
            },
            grid: {
                borderColor: colors.borderColor, strokeDashArray: 0,
                yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } },
                padding: { top: 0, right: 0, bottom: 0, left: 20 }
            },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.01, stops: [0, 100],
                    colorStops: [{ offset: 0, color: '#6366f1', opacity: 0.3 }, { offset: 100, color: '#6366f1', opacity: 0 }] }
            },
            theme: { mode: colors.isDark ? 'dark' : 'light' },
            tooltip: { theme: colors.tooltipTheme, y: { formatter: (val) => "Rp " + val.toLocaleString("id-ID") }, style: { fontSize: '11px', fontFamily: 'inherit' }, marker: { show: false } }
        });
        revChart.render();

        // ── 2. TRANSACTIONS BAR CHART ──────────────────────────────────────────
        var trxChart = new ApexCharts(document.querySelector("#transactionsChart"), {
            series: [{ name: 'Jml Sewa', data: @json($chartTransactions) }],
            chart: { type: 'bar', height: 220, fontFamily: 'inherit', toolbar: { show: false }, background: 'transparent', offsetX: -10, offsetY: 10 },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '40%' } },
            dataLabels: { enabled: false },
            colors: ['#10b981'],
            xaxis: {
                categories: @json($chartCategories),
                tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false },
                labels: { style: { colors: colors.textColor, fontFamily: 'inherit', fontSize: '10px' } },
                tickAmount: 8
            },
            yaxis: {
                labels: {
                    formatter: (val) => Math.round(val),
                    style: { colors: colors.textColor, fontFamily: 'inherit', fontSize: '10px' }
                }
            },
            grid: {
                borderColor: colors.borderColor,
                yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } },
                padding: { top: 0, right: 0, bottom: 0, left: 10 }
            },
            theme: { mode: colors.isDark ? 'dark' : 'light' },
            tooltip: { theme: colors.tooltipTheme, y: { formatter: (val) => val + " Orders" }, style: { fontSize: '11px', fontFamily: 'inherit' }, marker: { show: false } }
        });
        trxChart.render();

        // ── 3. PAYMENT METHOD DONUT CHART ─────────────────────────────────────
        var donutChart = new ApexCharts(document.querySelector("#paymentDonutChart"), {
            series: @json($paymentCounts),
            labels: @json($paymentLabels),
            chart: { type: 'donut', height: 220, fontFamily: 'inherit', background: 'transparent' },
            colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
            dataLabels: { enabled: true, style: { fontSize: '11px', fontFamily: 'inherit', colors: [colors.isDark ? '#fff' : '#111'] } },
            legend: { position: 'bottom', fontSize: '11px', fontFamily: 'inherit', labels: { colors: colors.textColor } },
            plotOptions: { pie: { donut: { size: '60%', labels: { show: true, total: { show: true, label: 'Total', color: colors.textColor, fontSize: '12px', fontFamily: 'inherit' } } } } },
            theme: { mode: colors.isDark ? 'dark' : 'light' },
            tooltip: { theme: colors.tooltipTheme, style: { fontSize: '11px', fontFamily: 'inherit' } }
        });
        donutChart.render();

        // ── Livewire data updates ──────────────────────────────────────────────
        Livewire.on('chartDataUpdated', (data) => {
            const d = Array.isArray(data) ? data[0] : data;
            revChart.updateOptions({ xaxis: { categories: d.categories } });
            revChart.updateSeries([{ name: 'Pendapatan', data: d.revenue }]);
            trxChart.updateOptions({ xaxis: { categories: d.categories } });
            trxChart.updateSeries([{ name: 'Jml Sewa', data: d.transactions }]);
        });

        // ── Theme toggle observer ─────────────────────────────────────────────
        const observer = new MutationObserver(() => {
            const c = getChartColors();
            const xStyle = { labels: { style: { colors: c.textColor, fontFamily: 'inherit', fontSize: '10px' } } };
            const yStyle = { labels: { style: { colors: c.textColor, fontFamily: 'inherit', fontSize: '10px' } } };
            const mode = { mode: c.isDark ? 'dark' : 'light' };
            revChart.updateOptions({ theme: mode, tooltip: { theme: c.tooltipTheme }, xaxis: xStyle, yaxis: yStyle, grid: { borderColor: c.borderColor } });
            trxChart.updateOptions({ theme: mode, tooltip: { theme: c.tooltipTheme }, xaxis: xStyle, yaxis: yStyle, grid: { borderColor: c.borderColor } });
            donutChart.updateOptions({ theme: mode, tooltip: { theme: c.tooltipTheme }, legend: { labels: { colors: c.textColor } },
                plotOptions: { pie: { donut: { labels: { total: { color: c.textColor } } } } },
                dataLabels: { style: { colors: [c.isDark ? '#fff' : '#111'] } }
            });
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    });
</script>