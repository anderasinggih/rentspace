<div class="relative min-h-screen pb-12 overflow-x-hidden" style="touch-action: pan-y;">
    <style>
        body {
            overflow-x: hidden !important;
            @apply bg-slate-50 dark:bg-background text-foreground;
            user-select: none;
        }

        .liquid-glass {
            @apply backdrop-blur-xl bg-background shadow-[0_8px_30px_rgb(0, 0, 0, 0.04)];
            border: 1px solid #cbd5e1;
        }

        .dark .liquid-glass {
            background: rgba(22, 22, 26, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .glass-highlight {
            @apply border-t border-border/40;
        }

        .dark .glass-highlight {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-stock-label {
            @apply text-muted-foreground/60 uppercase;
        }

        .dark .text-stock-label {
            color: rgba(255, 255, 255, 0.35);
        }

        .text-stock-up {
            color: #10b981;
        }

        .text-stock-down {
            color: #ef4444;
        }

        .apexcharts-tooltip {
            display: none !important;
            visibility: hidden !important;
        }

        .apexcharts-xaxistooltip {
            display: none !important;
            visibility: hidden !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <!-- 1. Snapshot Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 md:gap-3 mb-6">
        <div
            class="liquid-glass glass-highlight rounded-xl p-3 border-indigo-500/40 bg-indigo-500/5 transition-all hover:bg-indigo-500/10 dark:border-indigo-500/20">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[8px] md:text-[9px] font-semibold text-indigo-600 uppercase">Discount Spent</p>
                @if($gainDiscounts !== null)
                    <span class="text-[8px] font-bold {{ $gainDiscounts >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainDiscounts >= 0 ? '▲' : '▼' }}{{ abs($gainDiscounts) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-indigo-600/50">Rp</span>
                <span class="text-xl font-semibold text-indigo-600 leading-none">
                    {{ $periodDiscounts >= 1000 ? round($periodDiscounts / 1000, 1) . 'k' : number_format($periodDiscounts, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[8px] md:text-[9px] font-semibold text-stock-label uppercase">Order Queue</p>
                @if($gainPendingRentals !== null)
                    <span class="text-[8px] font-bold {{ $gainPendingRentals >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainPendingRentals >= 0 ? '▲' : '▼' }}{{ abs($gainPendingRentals) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-foreground leading-none">{{ $pendingRentals }}</span>
                <span class="text-[8px] font-semibold text-stock-label bg-white/5 px-1 rounded">Trx</span>
            </div>
        </div>

        <div
            class="liquid-glass glass-highlight rounded-xl p-3 border-amber-500/40 bg-amber-500/5 transition-all hover:bg-amber-500/10 dark:border-amber-500/20">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[8px] md:text-[9px] font-semibold text-amber-600 uppercase">Pending Balance</p>
                @if($gainPendingRevenue !== null)
                    <span class="text-[8px] font-bold {{ $gainPendingRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainPendingRevenue >= 0 ? '▲' : '▼' }}{{ abs($gainPendingRevenue) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-amber-600/50">Rp</span>
                <span class="text-xl font-semibold text-amber-600 leading-none">
                    {{ $pendingRevenue >= 1000 ? round($pendingRevenue / 1000, 1) . 'k' : number_format($pendingRevenue, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div
            class="liquid-glass glass-highlight rounded-xl p-3 border-emerald-500/40 bg-emerald-500/5 transition-all hover:bg-emerald-500/10 dark:border-emerald-500/20">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[8px] md:text-[9px] font-semibold text-emerald-600 uppercase">Unrealized Income</p>
                @if($gainUnrealizedRevenue !== null)
                    <span
                        class="text-[8px] font-bold {{ $gainUnrealizedRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainUnrealizedRevenue >= 0 ? '▲' : '▼' }}{{ abs($gainUnrealizedRevenue) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-emerald-600/50">Rp</span>
                <span class="text-xl font-semibold text-emerald-600 leading-none">
                    {{ $unrealizedRevenue >= 1000 ? round($unrealizedRevenue / 1000, 1) . 'k' : number_format($unrealizedRevenue, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[8px] md:text-[9px] font-semibold text-stock-label uppercase">Realized Today</p>
                @if($gainTodayRevenue !== null)
                    <span class="text-[8px] font-bold {{ $gainTodayRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainTodayRevenue >= 0 ? '▲' : '▼' }}{{ abs($gainTodayRevenue) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-stock-label opacity-40">Rp</span>
                <span class="text-xl font-semibold text-foreground leading-none">
                    {{ $todayRevenue >= 1000 ? round($todayRevenue / 1000, 1) . 'k' : number_format($todayRevenue, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[8px] md:text-[9px] font-semibold text-stock-label uppercase">Today's Rentals</p>
                @if($gainTodayRentals !== null)
                    <span class="text-[8px] font-bold {{ $gainTodayRentals >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainTodayRentals >= 0 ? '▲' : '▼' }}{{ abs($gainTodayRentals) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-foreground leading-none">{{ $todayRentals }}</span>
                <span class="text-[10px] font-medium text-stock-label uppercase">Units</span>
            </div>
        </div>
    </div>

    <!-- 2. Historical Section (Stockbit Style - Full Width) -->
    <div class="mb-4 px-1">
        <div class="flex items-center justify-between gap-1 w-full overflow-x-auto no-scrollbar">
            @php
                $presets = [
                    ['val' => '7', 'label' => '7D'],
                    ['val' => '30', 'label' => '1M'],
                    ['val' => '90', 'label' => '3M'],
                    ['val' => 'mth', 'label' => 'MTH'],
                    ['val' => 'ytd', 'label' => 'YTD'],
                    ['val' => 'all', 'label' => 'ALL'],
                ];
            @endphp
            @foreach($presets as $p)
                <button wire:click="selectPreset('{{ $p['val'] }}')"
                    class="flex-1 h-7 rounded text-[10px] font-bold transition-all shrink-0 {{ $preset === $p['val'] ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted/50 text-muted-foreground hover:bg-muted' }}">
                    {{ $p['label'] }}
                </button>
            @endforeach
            <button wire:click="selectPreset('custom')"
                class="px-4 h-7 rounded text-[10px] font-bold transition-all shrink-0 {{ $preset === 'custom' ? 'bg-amber-500 text-black' : 'bg-muted/50 text-muted-foreground hover:bg-muted' }}">
                C
            </button>
        </div>
    </div>

    <!-- 2.1 Custom Date Picker -->
    @if($preset === 'custom')
        <div class="mb-6 grid grid-cols-2 gap-3 liquid-glass p-3 rounded-xl animate-in fade-in slide-in-from-top-1">
            <div class="flex flex-col gap-1">
                <label class="text-[8px] font-bold text-stock-label uppercase px-1">Start Date</label>
                <input type="date" wire:model.live="startDate"
                    class="bg-background border border-border rounded h-8 text-[11px] text-foreground focus:ring-primary px-2 outline-none">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[8px] font-bold text-stock-label uppercase px-1">End Date</label>
                <input type="date" wire:model.live="endDate"
                    class="bg-background border border-border rounded h-8 text-[11px] text-foreground focus:ring-primary px-2 outline-none">
            </div>
        </div>
    @endif

    <!-- 3. Performance Summary -->
    <div class="mb-6 liquid-glass rounded-2xl overflow-hidden shadow-sm">
        <div class="grid grid-cols-3 divide-x divide-border border-b border-border">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Net Income</span>
                <span class="text-lg font-semibold text-foreground">
                    Rp{{ $periodNetRevenue >= 1000 ? round($periodNetRevenue / 1000, 1) . 'k' : number_format($periodNetRevenue, 0, ',', '.') }}
                </span>
                @if($gainNetRevenue !== null)
                    <div class="text-[10px] font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '+' : '' }}{{ $gainNetRevenue }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Affiliate Fee</span>
                <span class="text-lg font-semibold text-stock-down/70">
                    Rp{{ $periodCommissions >= 1000 ? round($periodCommissions / 1000, 1) . 'k' : number_format($periodCommissions, 0, ',', '.') }}
                </span>
            </div>
            <div class="p-4 flex flex-col gap-0.5 text-right">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Margin ROI</span>
                <span class="text-lg font-semibold text-stock-up">{{ round($profitEfficiency, 1) }}%</span>
            </div>
        </div>
        <div class="grid grid-cols-3 bg-muted/20 p-3 divide-x divide-border font-sans">
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Total Orders</span>
                <span class="text-xs font-bold text-foreground tracking-tight leading-none">{{ $periodRentals }} <span
                        class="text-[8px] text-stock-label">TRX</span></span>
            </div>
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Avg. AOV</span>
                <span class="text-xs font-bold text-foreground tracking-tight leading-none">
                    Rp{{ $avgOrderValue >= 1000 ? round($avgOrderValue / 1000, 1) . 'k' : number_format($avgOrderValue, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Avg. Duration</span>
                <span class="text-xs font-bold text-foreground tracking-tight leading-none">{{ round($avgDuration, 1) }}
                    Hours</span>
            </div>
        </div>
    </div>

    <!-- 4. Interactive Terminals -->
    <div class="flex flex-col gap-4 mb-6">
        <div class="liquid-glass rounded-2xl p-4 relative overflow-hidden h-[320px] md:h-[400px]">
            <div class="absolute top-8 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 class="text-[10px] font-semibold text-stock-label uppercase mb-0.5">Net Income Analysis</h3>
                <div class="flex items-baseline justify-center gap-2">
                    <div class="flex items-baseline gap-1">
                        <span
                            class="text-xs font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }} opacity-50">Rp</span>
                        <span id="chart-revenue-nominal"
                            class="text-3xl font-semibold text-foreground leading-none">0k</span>
                    </div>
                    @if($gainNetRevenue !== null)
                        <div id="chart-revenue-gain"
                            class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-opacity {{ $gainNetRevenue >= 0 ? 'bg-emerald-500/10 text-stock-up' : 'bg-red-500/10 text-stock-down' }}">
                            {{ $gainNetRevenue >= 0 ? '▲' : '▼' }} {{ abs($gainNetRevenue) }}%
                        </div>
                    @else
                        <div id="chart-revenue-gain"
                            class="transition-opacity px-1.5 py-0.5 rounded text-[10px] font-bold bg-muted/50 text-muted-foreground">
                            N/A</div>
                    @endif
                </div>
                <p class="text-[9px] font-medium text-muted-foreground mt-2">{{ $dateRangeLabel }}</p>
                <p id="chart-revenue-date"
                    class="text-[9px] font-semibold text-stock-label mt-4 opacity-0 transition-opacity">---</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-[200px] md:h-[280px]">
                <div id="revenueChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl p-4 relative overflow-hidden h-[320px] md:h-[400px]">
            <div class="absolute top-8 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 class="text-[10px] font-semibold text-stock-label uppercase mb-0.5">Order Traffic Pattern</h3>
                <div class="flex items-baseline justify-center gap-2">
                    <div class="flex items-baseline gap-1">
                        <span id="chart-trx-nominal"
                            class="text-3xl font-semibold text-foreground leading-none">0</span>
                        <span
                            class="text-xs font-semibold {{ $gainRentals >= 0 ? 'text-stock-up' : 'text-stock-down' }} opacity-50">Trx</span>
                    </div>
                    @if($gainRentals !== null)
                        <div id="chart-trx-gain"
                            class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-opacity {{ $gainRentals >= 0 ? 'bg-emerald-500/10 text-stock-up' : 'bg-red-500/10 text-stock-down' }}">
                            {{ $gainRentals >= 0 ? '▲' : '▼' }} {{ abs($gainRentals) }}%
                        </div>
                    @else
                        <div id="chart-trx-gain"
                            class="transition-opacity px-1.5 py-0.5 rounded text-[10px] font-bold bg-muted/50 text-muted-foreground">
                            N/A</div>
                    @endif
                </div>
                <p class="text-[9px] font-medium text-muted-foreground mt-2">{{ $dateRangeLabel }}</p>
                <p id="chart-trx-date"
                    class="text-[9px] font-semibold text-stock-label mt-4 opacity-0 transition-opacity">---</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-[200px] md:h-[280px]">
                <div id="transactionsChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl p-5 relative overflow-hidden h-auto">
            {{-- Dynamic Labels (Clear Row to prevent overlap) --}}
            <div class="flex flex-col items-center justify-center h-10 w-full pointer-events-none">
                <p id="hm-dynamic-val" class="text-md font-black text-foreground leading-none mb-2">0 Orders</p>
                <p id="hm-dynamic-date"
                    class="text-[10px] font-bold text-stock-label  opacity-0 transition-opacity whitespace-nowrap ">
                    ---</p>
            </div>

            <div class="flex gap-1 md:gap-4 flex-col md:flex-row mb-4">
                <div class="flex-1 overflow-x-auto no-scrollbar -mx-2 px-2"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    <div id="heatmapChart" class="min-w-[900px] h-[160px]" wire:ignore></div>
                </div>

                <div
                    class="flex flex-row md:flex-col gap-1.5 md:gap-2 shrink-0 md:border-l border-border md:pl-4 py-2 border-t md:border-t-0 mt-3 md:mt-0 pt-3 md:pt-0 overflow-x-auto no-scrollbar">
                    @foreach($availableYears as $year)
                        <button wire:click="setHeatmapYear({{ $year }})"
                            class="text-[9px] font-black transition-all px-2 md:px-0 py-1 rounded {{ $heatmapYear == $year ? 'text-primary' : 'text-stock-label hover:text-foreground' }}">
                            {{ $year }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-border">
                <h3 class="text-[9px] font-bold text-stock-label tracking-tight uppercase">Yearly Activity Monitor</h3>
                <div class="flex items-center gap-1.5 text-[8px] font-bold text-stock-label uppercase">
                    <span>Less</span>
                    <div class="flex gap-1">
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#ebedf0] dark:bg-zinc-800"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#d1fae5] dark:bg-[#064e3b]"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#6ee7b7] dark:bg-[#065f46]"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#10b981] dark:bg-[#059669]"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#047857] dark:bg-[#34d399]"></div>
                    </div>
                    <span>More</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Secondary Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-1 liquid-glass rounded-2xl p-5 flex flex-col h-[260px]">
            <h3 class="text-[10px] font-semibold text-stock-label mb-3 uppercase leading-none">Payment Methods</h3>
            <div class="flex-1 flex items-center justify-center">
                <div id="paymentDonutChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="lg:col-span-2 liquid-glass rounded-2xl overflow-hidden h-[260px]">
            <div
                class="p-3 border-b border-border bg-muted/20 text-[10px] font-semibold text-foreground opacity-60 uppercase">
                Top Performing Units</div>
            <div class="overflow-y-auto h-[215px]">
                <table class="w-full text-left font-sans text-[11px]">
                    <thead
                        class="text-[9px] font-semibold text-stock-label border-b border-border uppercase sticky top-0 bg-background z-10">
                        <tr>
                            <th class="px-4 py-2">Unit</th>
                            <th class="px-4 py-2 text-center">Rented</th>
                            <th class="px-4 py-2 text-right">Net Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($topUnits as $tu)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3 font-semibold text-foreground uppercase">
                                    {{ $tu->unit ? $tu->unit->seri : '---' }}
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ $tu->rent_count }}x</td>
                                <td class="px-4 py-3 text-right font-semibold text-stock-up">
                                    Rp{{ number_format($tu->revenue, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. Monitor & Tenants Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl">
            <div class="px-5 py-3.5 border-b border-border bg-primary/5 flex items-center justify-between uppercase">
                <span class="text-[11px] font-semibold text-primary">Live Activity Monitor</span>
            </div>
            <div class="overflow-x-auto max-h-[300px]">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead
                        class="bg-muted/10 text-[9px] font-semibold text-stock-label uppercase sticky top-0 bg-background z-10">
                        <tr>
                            <th class="px-6 py-3">Unit</th>
                            <th class="px-6 py-3">Tenant</th>
                            <th class="px-6 py-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($activeRentals as $rental)
                            <tr class="hover:bg-muted/30 transition-all">
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($rental->units as $u)
                                            <span
                                                class="px-1.5 py-0.5 rounded bg-muted text-[8px] md:text-[10px] font-bold text-foreground border border-border uppercase">{{ $u->seri }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-foreground uppercase">
                                    {{ explode(' ', trim($rental->nama))[0] }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($rental->status === 'pending')
                                        <x-ui.badge variant="amber" class="text-[9px]">Pending</x-ui.badge>
                                    @elseif($rental->status === 'paid')
                                        <x-ui.badge variant="blue" class="text-[9px]">Paid</x-ui.badge>
                                    @elseif($rental->status === 'renting')
                                        <x-ui.badge variant="emerald" class="text-[9px]">Rent</x-ui.badge>
                                    @elseif($rental->status === 'completed')
                                        <x-ui.badge variant="green" class="text-[9px]">Done</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="red" class="text-[9px]">Cancel</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3"
                                    class="px-6 py-12 text-center text-muted-foreground text-[10px] font-semibold uppercase">
                                    No Units Currently Rented</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl">
            <div class="px-5 py-3.5 border-b border-border bg-primary/5 flex items-center justify-between uppercase">
                <span class="text-[11px] font-semibold text-primary">Top Active Tenants</span>
            </div>
            <div class="overflow-y-auto max-h-[300px]">
                <table class="w-full text-left font-sans text-[11px]">
                    <thead
                        class="text-[9px] font-semibold text-stock-label border-b border-border uppercase sticky top-0 bg-background z-10">
                        <tr>
                            <th class="px-4 py-2">Tenant</th>
                            <th class="px-4 py-2 text-center">Freq</th>
                            <th class="px-4 py-2 text-right">Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($topTenants as $tenant)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-foreground uppercase">{{ $tenant->nama }}</div>
                                    <div class="text-[8px] text-stock-label mt-0.5">{{ $tenant->no_wa }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ $tenant->total_rentals }}x</td>
                                <td class="px-4 py-3 text-right font-semibold text-foreground">
                                    Rp{{ number_format($tenant->total_spent, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- 7. Realized Today Breakdown -->
    <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl mb-6">
        <div class="px-5 py-3.5 border-b border-border bg-emerald-500/5 flex items-center justify-between uppercase">
            <span class="text-[11px] font-semibold text-emerald-600">Realized Today Transactions</span>
            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-full">
                Rp{{ number_format($todayRevenue, 0, ',', '.') }}
            </span>
        </div>
        <div class="overflow-x-auto max-h-[400px]">
            <table class="w-full text-left border-collapse text-[11px]">
                <thead class="bg-muted/10 text-[9px] font-semibold text-stock-label uppercase sticky top-0 bg-background z-10">
                    <tr>
                        <th class="px-4 py-2">Time</th>
                        <th class="px-4 py-2">Units</th>
                        <th class="px-4 py-2">Tenant</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border font-sans">
                    @forelse($todayRealizedRentals as $rental)
                        <tr class="hover:bg-muted/30 transition-all">
                            <td class="px-4 py-2.5 text-muted-foreground">
                                {{ $rental->paid_at ? $rental->paid_at->format('H:i') : '-' }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-1.5 py-0.5 rounded bg-muted text-[8px] font-bold text-foreground border border-border uppercase">
                                            {{ $u->seri }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="font-semibold text-foreground uppercase text-[10px] truncate max-w-[80px]">
                                    {{ explode(' ', trim($rental->nama))[0] }}
                                </div>
                                <div class="text-[8px] text-stock-label">{{ $rental->no_wa }}</div>
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">
                                Rp{{ number_format($rental->grand_total, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground text-[10px] font-semibold uppercase">
                                No Transactions Realized Today Yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Export Section -->
        <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl mb-6">
            <div class="px-5 py-3.5 border-b border-border bg-primary/5 flex items-center justify-between uppercase">
                <span class="text-[11px] font-semibold text-primary">Analytic Performance Reports</span>
            </div>

            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                    <div>
                        <h2 class="text-lg font-bold text-foreground">Export Engine</h2>
                        <p class="text-[11px] text-muted-foreground mt-1">Generate high-fidelity, boardroom-ready PDF
                            analytics.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            class="bg-background/80 border border-border rounded-xl p-1 flex items-center gap-1 shadow-sm">
                            <select wire:model.live="reportMonth"
                                class="bg-transparent border-none text-[11px] font-semibold focus:ring-0 px-2 cursor-pointer h-8">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                @endforeach
                            </select>
                            <div class="w-px h-3 bg-border"></div>
                            <select wire:model.live="reportYear"
                                class="bg-transparent border-none text-[11px] font-semibold focus:ring-0 px-2 cursor-pointer h-8">
                                @foreach($availableYears as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="generateMonthlyReport" wire:loading.attr="disabled"
                            class="h-10 px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-indigo-500/20 active:scale-95">
                            <span wire:loading.remove wire:target="generateMonthlyReport">Monthly</span>
                            <span wire:loading wire:target="generateMonthlyReport">Generating...</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" class="opacity-70">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" x2="12" y1="15" y2="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4">
                    <div
                        class="flex-1 bg-muted/20 border border-border/50 rounded-2xl p-6 flex flex-col justify-between group">
                        <div>
                            <h3 class="font-bold text-foreground text-sm">Annual Review {{ $heatmapYear }}</h3>
                            <p class="text-[10px] text-muted-foreground mt-1">Full year financial summary and unit
                                performance metrics.</p>
                        </div>
                        <button wire:click="generateYearlyReport" wire:loading.attr="disabled"
                            class="mt-6 h-9 px-5 w-fit bg-indigo-600/10 hover:bg-indigo-600 hover:text-white text-indigo-600 rounded-xl text-[10px] font-bold transition-all flex items-center gap-2 active:scale-95">
                            <span wire:loading.remove wire:target="generateYearlyReport">Download Annual PDF</span>
                            <span wire:loading wire:target="generateYearlyReport">Wait...</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" class="opacity-70">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" x2="12" y1="15" y2="3" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 bg-muted/20 border border-border/50 rounded-2xl p-6 flex items-center gap-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-[11px] font-bold text-indigo-600/80 uppercase">Verified Insights</h4>
                            <p class="text-[10px] text-muted-foreground mt-0.5 leading-relaxed">
                                Data is strictly audited against validated system transactions for accuracy.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @script
    <script>
        if (typeof ApexCharts !== 'undefined') {
            const initCharts = () => {
                // -- Elements --
                const elRevVal = document.getElementById('chart-revenue-nominal');
                const elRevDate = document.getElementById('chart-revenue-date');
                const elTrxVal = document.getElementById('chart-trx-nominal');
                const elTrxDate = document.getElementById('chart-trx-date');

                // -- Data --
                const netData = @json($chartNetRevenue);
                const trxData = @json($chartTransactions);
                const prevNetData = @json($prevNetRevenue);
                const prevTrxData = @json($prevTransactions);
                const categories = @json($chartCategories);

                const latRev = netData.length > 0 ? netData[netData.length - 1] : 0;
                const latTrx = trxData.length > 0 ? trxData[trxData.length - 1] : 0;

                if (elRevVal) elRevVal.innerText = latRev.toLocaleString('id-ID');
                if (elTrxVal) elTrxVal.innerText = latTrx.toLocaleString('id-ID');

                const gainRev = @json($gainNetRevenue);
                const gainTrx = @json($gainRentals);
                const revColor = (gainRev >= 0) ? '#10b981' : '#ef4444';
                const trxColor = (gainTrx >= 0) ? '#10b981' : '#ef4444';

                const currentYear = new Date().getFullYear();
                const fmtCategories = categories.map(cat => {
                    // If it's just "28 Apr", add current year. If it's "Apr 2026", leave it.
                    if (cat.split(' ').length === 2 && !isNaN(cat.split(' ')[0])) {
                        return cat + ' ' + currentYear;
                    }
                    return cat;
                });

                // -- Theme Helper --
                const getChartStyles = () => {
                    const isDark = document.documentElement.classList.contains('dark');
                    return {
                        grid: isDark ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.08)',
                        crosshair: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        tooltip: isDark ? 'dark' : 'light',
                        label: isDark ? '#666' : '#999',
                        bg: isDark ? '#0c0c0e' : '#ffffff'
                    };
                };

                let styles = getChartStyles();

                // -- Chart Helper Config --
                const baseConfig = (seriesData, prevSeriesData, originalColor, nominalEl, dateEl, gainEl, originalTotalGain, isTrx = false) => {
                    let currentStatusColor = originalColor;

                    return {
                        series: [{ name: isTrx ? 'Orders' : 'Net', data: seriesData }],
                        chart: {
                            type: 'area', height: '100%', fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, sparkline: { enabled: true },
                            events: {
                                mouseMove: function (ev, ctx, config) {
                                    if (config.dataPointIndex !== -1 && ctx.w.globals) {
                                        try {
                                            const series = ctx.w.globals.series[0];
                                            const v = series[config.dataPointIndex];
                                            const l = fmtCategories[config.dataPointIndex];

                                            if (v !== undefined && nominalEl) {
                                                nominalEl.innerText = v.toLocaleString('id-ID');
                                                if (dateEl) { dateEl.innerText = l || '---'; dateEl.style.opacity = '1'; }

                                                // Improved Gain Calculation: Compare CURRENT Accumulation with PREVIOUS PARALLEL Accumulation
                                                if (gainEl && prevSeriesData && prevSeriesData.length > config.dataPointIndex) {
                                                    const pv = prevSeriesData[config.dataPointIndex];
                                                    let pointGain = 0;
                                                    if (pv > 0) {
                                                        pointGain = ((v - pv) / pv) * 100;
                                                    } else {
                                                        pointGain = v > 0 ? 100 : 0;
                                                    }

                                                    const isUp = pointGain >= 0;
                                                    const targetColor = isUp ? '#10b981' : '#ef4444';

                                                    // Dynamic Color Change
                                                    if (currentStatusColor !== targetColor) {
                                                        currentStatusColor = targetColor;
                                                        ctx.updateOptions({
                                                            colors: [targetColor],
                                                            fill: { gradient: { stops: [0, 90, 100] } },
                                                            markers: { strokeColors: targetColor }
                                                        }, false, false);
                                                    }

                                                    gainEl.innerHTML = `${isUp ? '▲' : '▼'} ${Math.abs(pointGain).toFixed(1)}%`;
                                                    gainEl.className = `px-1.5 py-0.5 rounded text-[10px] font-bold transition-opacity ${isUp ? 'bg-emerald-500/10 text-stock-up' : 'bg-red-500/10 text-stock-down'}`;
                                                } else if (gainEl) {
                                                    gainEl.innerHTML = '---';
                                                    gainEl.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold transition-opacity bg-muted/50 text-muted-foreground';
                                                }
                                            }
                                        } catch (e) { }
                                    }
                                },
                                mouseLeave: function (ev, ctx) {
                                    if (nominalEl) nominalEl.innerText = (seriesData.length > 0 ? seriesData[seriesData.length - 1] : 0).toLocaleString('id-ID');
                                    if (dateEl) dateEl.style.opacity = '0';

                                    // Restore Color
                                    if (currentStatusColor !== originalColor) {
                                        currentStatusColor = originalColor;
                                        ctx.updateOptions({
                                            colors: [originalColor],
                                            markers: { strokeColors: originalColor }
                                        }, false, false);
                                    }

                                    // Restore Original Total Period Gain
                                    if (gainEl) {
                                        if (originalTotalGain !== null) {
                                            const isUp = originalTotalGain >= 0;
                                            gainEl.innerHTML = `${isUp ? '▲' : '▼'} ${Math.abs(originalTotalGain).toFixed(1)}%`;
                                            gainEl.className = `px-1.5 py-0.5 rounded text-[10px] font-bold transition-opacity ${isUp ? 'bg-emerald-500/10 text-stock-up' : 'bg-red-500/10 text-stock-down'}`;
                                        } else {
                                            gainEl.innerHTML = 'N/A';
                                            gainEl.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold transition-opacity bg-muted/50 text-muted-foreground';
                                        }
                                    }
                                }
                            }
                        },
                        grid: {
                            show: true,
                            borderColor: styles.grid,
                            strokeDashArray: 4,
                            position: 'back',
                            xaxis: { lines: { show: true } },
                            yaxis: { lines: { show: true } }
                        },
                        colors: [originalColor],
                        stroke: { width: 2, curve: 'smooth' },
                        fill: { type: 'gradient', gradient: { shade: styles.tooltip, type: "vertical", shadeIntensity: 0.5, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                        markers: { size: 0, strokeColors: originalColor, strokeWidth: 1, hover: { size: 2.5 } },
                        tooltip: { enabled: true, theme: styles.tooltip, shared: false, intersect: false, marker: { show: false }, x: { show: false }, y: { show: false } },
                        xaxis: {
                            categories: fmtCategories,
                            tickAmount: 6,
                            crosshairs: { show: true, width: 1, position: 'back', stroke: { color: styles.crosshair, width: 1, dashArray: 4 } },
                            tooltip: { enabled: false }
                        },
                        yaxis: { tickAmount: 4, tooltip: { enabled: false } }
                    };
                };

                const elRevGain = document.getElementById('chart-revenue-gain');
                const elTrxGain = document.getElementById('chart-trx-gain');

                const rv = new ApexCharts(document.querySelector("#revenueChart"), baseConfig(netData, prevNetData, revColor, elRevVal, elRevDate, elRevGain, gainRev));
                const tr = new ApexCharts(document.querySelector("#transactionsChart"), baseConfig(trxData, prevTrxData, trxColor, elTrxVal, elTrxDate, elTrxGain, gainTrx, true));

                rv.render();
                tr.render();

                let dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                    series: @json($paymentCounts),
                    chart: { type: 'donut', height: '100%', toolbar: { show: false } },
                    colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#18181b'],
                    labels: @json($paymentLabels),
                    dataLabels: { enabled: true, formatter: (val, opts) => opts.w.globals.labels[opts.seriesIndex], style: { fontSize: '9px', fontWeight: 600 } },
                    legend: { show: false },
                    plotOptions: { pie: { donut: { size: '75%', labels: { show: true, name: { show: true, fontSize: '10px', color: styles.label, offsetY: -5 }, value: { show: true, fontSize: '14px', color: styles.label === '#666' ? '#fff' : '#000', offsetY: 5, fontWeight: 700 } } } } },
                    stroke: { width: 1, colors: [styles.grid] }
                });
                dn.render();

                // -- Heatmap Range Helper --
                const getHeatmapRanges = (isDark) => {
                    return isDark ? [
                        { from: 0, to: 0, color: 'rgba(255,255,255,0.06)' },
                        { from: 1, to: 1, color: '#064e3b' },
                        { from: 2, to: 5, color: '#065f46' },
                        { from: 6, to: 9, color: '#059669' },
                        { from: 10, to: 1000, color: '#34d399' }
                    ] : [
                        { from: 0, to: 0, color: '#ebedf0' },
                        { from: 1, to: 1, color: '#d1fae5' },
                        { from: 2, to: 5, color: '#6ee7b7' },
                        { from: 6, to: 9, color: '#10b981' },
                        { from: 10, to: 1000, color: '#047857' }
                    ];
                };

                let hm = new ApexCharts(document.querySelector("#heatmapChart"), {
                    series: @json($heatmapData),
                    chart: {
                        type: 'heatmap', height: '100%', fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 800 },
                        events: {
                            mouseMove: function (ev, ctx, config) {
                                if (config.seriesIndex !== -1 && config.dataPointIndex !== -1) {
                                    const v = ctx.w.globals.initialSeries[config.seriesIndex].data[config.dataPointIndex].y;
                                    const d = ctx.w.globals.initialSeries[config.seriesIndex].data[config.dataPointIndex].d;
                                    const evV = document.getElementById('hm-dynamic-val');
                                    const evD = document.getElementById('hm-dynamic-date');
                                    if (evV) evV.innerText = v + ' Orders';
                                    if (evD) { evD.innerText = d; evD.style.opacity = '1'; }
                                }
                            },
                            mouseLeave: function () {
                                const evV = document.getElementById('hm-dynamic-val');
                                const evD = document.getElementById('hm-dynamic-date');
                                if (evV) evV.innerText = '0 Orders';
                                if (evD) evD.style.opacity = '0';
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    colors: ["#10b981"],
                    xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false }, crosshairs: { show: false } },
                    yaxis: { labels: { style: { fontSize: '9px', colors: styles.label, fontWeight: 500 }, offsetX: -12 } },
                    grid: { show: false, padding: { top: -20, bottom: 0, left: 20, right: 10 } },
                    legend: { show: false },
                    states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
                    plotOptions: {
                        heatmap: {
                            radius: 4,
                            enableShades: false,
                            useFillColorAsStroke: false,
                            colorScale: {
                                ranges: getHeatmapRanges(document.documentElement.classList.contains('dark'))
                            }
                        }
                    },
                    stroke: { width: 4, colors: [styles.bg] },
                    tooltip: {
                        enabled: true, theme: styles.tooltip,
                        custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                            const val = series[seriesIndex][dataPointIndex];
                            const date = w.globals.initialSeries[seriesIndex].data[dataPointIndex].d;
                            return `<div class="px-3 py-2 bg-background border border-border rounded-lg shadow-xl">
                                    <div class="text-[10px] font-bold text-foreground mb-1">${val} Orders</div>
                                    <div class="text-[8px] font-medium text-muted-foreground uppercase tracking-widest">${date}</div>
                                </div>`;
                        }
                    }
                });
                hm.render();

                let latestRev = latRev;
                let latestTrx = latTrx;

                const snapBack = () => {
                    if (elRevVal) elRevVal.innerText = latestRev.toLocaleString('id-ID');
                    if (elRevDate) elRevDate.style.opacity = '0';
                    if (elTrxVal) elTrxVal.innerText = latestTrx.toLocaleString('id-ID');
                    if (elTrxDate) elTrxDate.style.opacity = '0';
                };

                Livewire.on('chartDataUpdated', (d) => {
                    const x = d[0] || d;
                    rv?.updateSeries([{ name: 'Net', data: x.netRevenue }]);
                    tr?.updateSeries([{ name: 'Orders', data: x.transactions }]);
                    latestRev = x.netRevenue.length > 0 ? x.netRevenue[x.netRevenue.length - 1] : 0;
                    latestTrx = x.transactions.length > 0 ? x.transactions[x.transactions.length - 1] : 0;
                    hm?.updateSeries(x.heatmap);
                    snapBack();
                });

                window.addEventListener('theme-changed', (e) => {
                    const s = getChartStyles();
                    const isDark = document.documentElement.classList.contains('dark');

                    const commonOpt = {
                        grid: { borderColor: s.grid },
                        tooltip: { theme: s.tooltip },
                        xaxis: { crosshairs: { stroke: { color: s.crosshair } } }
                    };

                    rv.updateOptions(commonOpt);
                    tr.updateOptions(commonOpt);

                    hm.updateOptions({
                        grid: { borderColor: s.grid },
                        stroke: { width: 4, colors: [s.bg] },
                        tooltip: { theme: s.tooltip },
                        yaxis: { labels: { style: { colors: s.label } } },
                        plotOptions: {
                            heatmap: {
                                colorScale: {
                                    ranges: getHeatmapRanges(isDark)
                                }
                            }
                        }
                    });
                });

                ['#revenueChart', '#transactionsChart', '#heatmapChart'].forEach(id => {
                    const el = document.querySelector(id);
                    if (el) {
                        ['mouseup', 'touchend', 'mouseleave'].forEach(evt => {
                            el.addEventListener(evt, snapBack);
                        });
                    }
                });

            };
            initCharts();
        }
    </script>
    @endscript
</div>