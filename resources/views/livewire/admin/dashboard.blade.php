<div class="pb-10">

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto mb-2">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Dashboard Admin</h1>
            <p class="mt-2 text-sm text-muted-foreground">Ringkasan statistik, grafis pendapatan, dan
                analisis penyewaan.</p>
        </div>

    </div>

    <!-- Snapshot Metrics -->
    <div>
        <h2 class="text-sm font-semibold text-foreground mb-3 border-b border-border pb-2">Status & Hari Ini (Real-time)
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
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
                <p class="text-[10px] font-bold text-muted-foreground mt-1">Total: Rp
                    {{ number_format($pendingRevenue / 1000, 0, ',', '.') }}k
                </p>
            </div>
            <div
                class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between border-l-4 border-l-emerald-500">
                <h3 class="text-xs font-semibold text-emerald-600 mb-1 uppercase tracking-wider">Pendapatan Hari Ini
                </h3>
                <p class="text-xl md:text-2xl font-bold text-emerald-600">Rp {{ number_format(
    $todayRevenue / 1000,
    0,
    ',',
    '.'
) }}k</p>
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
            @php
                function gainBadge($gain, $abs = null)
                {
                    if ($gain === null)
                        return '<span class="text-[10px] text-muted-foreground">vs periode lalu: -</span>';
                    $isPositive = $gain >= 0;
                    $color = $isPositive
                        ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300 border-green-200/50 dark:border-green-900/50'
                        : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300 border-red-200/50 dark:border-red-900/50';
                    $arrow = $isPositive ? '▲' : '▼';
                    $absText = $abs !== null
                        ? ' (' . ($abs >= 0 ? '+' : '') . 'Rp ' . number_format(abs($abs) / 1000, 0, ',', '.') . 'k)'
                        : '';
                    return '<span class="inline-flex items-center border rounded-md px-1.5 py-0.5 text-[10px] font-semibold ' . $color . '">'
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
                <div class="mt-2 flex items-center gap-2">
                    {!! gainBadge($gainRevenue, $gainAbsRevenue) !!}
                    <span
                        class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950 px-1.5 py-0.5 rounded border border-emerald-100 dark:border-emerald-900 shadow-sm">
                        Net: Rp {{ number_format($periodNetRevenue / 1000, 0, ',', '.') }}k
                    </span>
                </div>
            </div>
            <div
                class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between border-l-4 border-l-red-500/50">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Komisi Affiliator</h3>
                <p class="text-lg md:text-xl font-bold text-red-500">Rp {{ number_format($periodCommissions, 0, ',', '.')
                    }}</p>
                <div class="mt-2 text-[10px] text-muted-foreground font-medium uppercase tracking-tight">Potongan Omset
                </div>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Diskon Keluar</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">Rp {{ number_format($periodDiscounts, 0, ',', '.')
                    }}</p>
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
                            number_format($tu->revenue / 1000, 0, ',', '.') }}k</td>
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
                            number_format($tenant->total_spent / 1000, 0, ',', '.') }}k</td>
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

        <!-- Analisis Performa Affiliator -->
        <div
            class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col lg:col-span-2">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight text-primary">Top Performa Affiliator</h2>
                <p class="text-[11px] text-muted-foreground mt-1">Berdasarkan komisi yang dihasilkan di periode terpilih
                </p>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-xs text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Nama Affiliator</th>
                            <th class="px-4 py-2 font-medium text-center">Referral Closing</th>
                            <th class="px-4 py-2 font-medium text-right">Total Komisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topAffiliates as $ta)
                                            <tr class="hover:bg-muted/30">
                                                <td class="px-4 py-3 text-foreground font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="h-6 w-6 rounded-full bg-primary/10 flex items-center justify-center text-[10px] font-black text-primary border border-primary/20">
                                                            {{ substr($ta->affiliator->name ?? 'A', 0, 1) }}
                                                        </div>
                                                        {{ $ta->affiliator->name ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center font-bold text-foreground">{{ $ta->total_trx }}x</td>
                                                <td class="px-4 py-3 text-right font-black text-red-500">Rp {{
                            number_format($ta->total_commission, 0, ',', '.') }}</td>
                                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-muted-foreground text-[10px]">Belum ada
                                    aktivitas affiliator di periode ini.</td>
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
            <h2 class="text-sm font-semibold leading-none tracking-tight">Status Sewa Berjalan</h2>
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
                            $diffInHours = now()->diffInHours($end, false);

                            // Human readable diff
                            $totalMinutes = abs(now()->diffInMinutes($end));
                            $h = floor($totalMinutes / 60);
                            $m = $totalMinutes % 60;
                            $diffText = ($h > 0 ? $h . ' jam ' : '') . ($m > 0 ? $m . ' menit' : ($h == 0 ? '0 menit' : ''));
                        @endphp
                        <tr class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium text-foreground">
                                @foreach($rental->units as $u)
                                    {{ $u->seri }}{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                                @if($rental->units->isEmpty()) Terhapus @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="font-medium text-foreground">{{ $rental->nama }}</span>
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="text-[9px] font-bold text-primary uppercase tracking-tighter">{{ $rental->booking_code }}</span>
                                        <span class="text-[9px] text-muted-foreground/60">•</span>
                                        <span class="text-[9px] text-muted-foreground">{{ $rental->no_wa }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $end->format('d M, H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($diffInHours < 0)
                                    <x-ui.badge variant="red" class="text-[10px] font-bold uppercase">
                                        Telat Masuk
                                    </x-ui.badge>
                                @elseif($diffInHours < 3)
                                    <x-ui.badge variant="amber" class="text-[10px] font-bold uppercase">
                                        Sisa {{ $diffText }}
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="green" class="text-[10px] font-bold uppercase">
                                        Aman
                                    </x-ui.badge>
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

@script
<script>
    if (typeof ApexCharts !== 'undefined') {
        const initCharts = () => {
            const chartDom = document.querySelector("#revenueChart");
            if (!chartDom) return; // Not on dashboard or not ready

            function getChartColors() {
                const isDark = document.documentElement.classList.contains('dark');
                const style = getComputedStyle(document.documentElement);
                const resolve = (v) => `hsl(${style.getPropertyValue(v).trim()})`;
                return {
                    isDark,
                    textColor: isDark ? '#a1a1aa' : '#71717a',
                    borderColor: isDark ? '#27272a' : '#e4e4e7',
                    tooltipTheme: isDark ? 'dark' : 'light',
                };
            }

            let colors = getChartColors();

            // ── 1. REVENUE AREA CHART ──────────────────────────────────────────────
            var revChart = new ApexCharts(document.querySelector("#revenueChart"), {
                series: [
                    { name: 'Omset Kotor', data: @json($chartRevenue) },
                    { name: 'Omset Bersih', data: @json($chartNetRevenue) }
                ],
                chart: {
                    type: 'area', height: 300, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent', offsetX: -10, offsetY: 10,
                    events: {
                        mouseMove: function (event, chartContext, config) {
                            if (config.dataPointIndex !== -1 && typeof window.navigator.vibrate === 'function') {
                                if (window.lastVibratePoint !== config.dataPointIndex) {
                                    window.navigator.vibrate(10);
                                    window.lastVibratePoint = config.dataPointIndex;
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2, colors: ['#6366f1', '#10b981'] },
                xaxis: {
                    categories: @json($chartCategories),
                    tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false },
                    labels: {
                        hideOverlappingLabels: true,
                        rotate: 0,
                        rotateAlways: false,
                        minHeight: 20,
                        style: { colors: colors.textColor, fontFamily: 'inherit', fontSize: '9px' }
                    },
                    tickAmount: window.innerWidth < 640 ? 4 : 8
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
                    gradient: {
                        shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.01, stops: [0, 100],
                        colorStops: [
                            { offset: 0, color: '#6366f1', opacity: 0.2 },
                            { offset: 100, color: '#6366f1', opacity: 0 },
                            { offset: 0, color: '#10b981', opacity: 0.1 },
                            { offset: 100, color: '#10b981', opacity: 0 }
                        ]
                    }
                },
                colors: ['#6366f1', '#10b981'],
                theme: { mode: colors.isDark ? 'dark' : 'light' },
                legend: {
                    show: true,
                    position: 'bottom',
                    horizontalAlign: 'left',
                    offsetY: 8,
                    itemMargin: { horizontal: 10, vertical: 5 },
                    labels: { colors: colors.textColor, useSeriesColors: false }
                },
                tooltip: { theme: colors.tooltipTheme, y: { formatter: (val) => "Rp " + val.toLocaleString("id-ID") }, style: { fontSize: '11px', fontFamily: 'inherit' }, marker: { show: true } }
            });
            revChart.render();

            // ── 2. TRANSACTIONS BAR CHART ──────────────────────────────────────────
            var trxChart = new ApexCharts(document.querySelector("#transactionsChart"), {
                series: [{ name: 'Jml Sewa', data: @json($chartTransactions) }],
                chart: {
                    type: 'bar', height: 300, fontFamily: 'inherit', toolbar: { show: false }, background: 'transparent', offsetX: -10, offsetY: 10
                },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '40%' } },
                dataLabels: { enabled: false },
                colors: ['#10b981'],
                xaxis: {
                    categories: @json($chartCategories),
                    tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false },
                    labels: {
                        hideOverlappingLabels: true,
                        rotate: 0,
                        rotateAlways: false,
                        minHeight: 20,
                        style: { colors: colors.textColor, fontFamily: 'inherit', fontSize: '9px' }
                    },
                    tickAmount: window.innerWidth < 640 ? 4 : 8
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
                legend: {
                    show: true,
                    position: 'bottom',
                    horizontalAlign: 'left',
                    offsetY: 8,
                    itemMargin: { horizontal: 10, vertical: 5 },
                    labels: { colors: colors.textColor, useSeriesColors: false }
                },
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
                legend: {
                    position: 'bottom',
                    fontSize: '10px',
                    fontFamily: 'inherit',
                    labels: { colors: colors.textColor },
                    itemMargin: { horizontal: 8, vertical: 4 }
                },
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', color: colors.textColor, fontSize: '14px', fontWeight: 600, fontFamily: 'inherit' } } } } },
                theme: { mode: colors.isDark ? 'dark' : 'light' },
                tooltip: { theme: colors.tooltipTheme, style: { fontSize: '11px', fontFamily: 'inherit' } }
            });
            donutChart.render();

            // ── Livewire data updates ──────────────────────────────────────────────
            Livewire.on('chartDataUpdated', (data) => {
                const d = Array.isArray(data) ? data[0] : data;
                revChart.updateOptions({ xaxis: { categories: d.categories } });
                revChart.updateSeries([
                    { name: 'Omset Kotor', data: d.revenue },
                    { name: 'Omset Bersih', data: d.netRevenue }
                ]);
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
                donutChart.updateOptions({
                    theme: mode, tooltip: { theme: c.tooltipTheme }, legend: { labels: { colors: c.textColor } },
                    plotOptions: { pie: { donut: { labels: { total: { color: c.textColor } } } } },
                    dataLabels: { style: { colors: [c.isDark ? '#fff' : '#111'] } }
                });
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        };

        initCharts();
    }
</script>
@endscript