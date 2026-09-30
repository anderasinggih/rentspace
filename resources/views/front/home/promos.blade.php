{{-- Promo Section --}}
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
    <div x-data="{ visible: false }" x-intersect.once="visible = true"
        :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
        class="text-center mb-8 transition-all duration-1000 ease-out">
        <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Promo Spesial Aktif</h2>
        <p class="mt-4 text-sm sm:text-base text-muted-foreground">Promo yang tersedia pada saat ini.</p>
    </div>

    <div x-data="{ expandedPromo: false, visible: false }" x-intersect.once="visible = true"
        :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-20'"
        class="mt-8 mb-16 w-full transition-all duration-1000 delay-100 ease-out">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            @foreach($promos as $promo)
                <a href="{{ route('public.booking') }}" wire:navigate
                    :class="{ 
                        'hidden': !expandedPromo && {{ $loop->index }} >= 2, 
                        'sm:block': !expandedPromo && {{ $loop->index }} >= 2 && {{ $loop->index }} < 4,
                        'sm:hidden': !expandedPromo && {{ $loop->index }} >= 4,
                        'block': expandedPromo || {{ $loop->index }} < 2 
                    }"
                    class="p-4 sm:p-5 bg-card text-card-foreground border border-border rounded-xl shadow-sm hover:border-emerald-500/50 hover:shadow-md transition-all duration-200 group relative overflow-hidden">
                    
                    <div class="relative z-10 flex items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-3 gap-3">
                                <h3 class="font-bold text-foreground text-sm sm:text-base truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-300">
                                    {{ $promo->nama_promo }}</h3>
                                <x-ui.badge variant="green"
                                    class="uppercase tracking-tight text-[9px] sm:text-[10px] px-1.5 sm:px-2 py-0.5 shrink-0 animate-pulse">
                                    Promo Aktif
                                </x-ui.badge>
                            </div>

                            @php
                                $durasi = $promo->syarat_minimal_durasi . ' ' . ucfirst($promo->syarat_tipe_durasi);
                                $promoText = '';
                                if ($promo->tipe === 'diskon_persen')
                                    $promoText = "Diskon " . $promo->value . "%";
                                elseif ($promo->tipe === 'diskon_nominal')
                                    $promoText = "Potongan Rp " . number_format($promo->value, 0, ',', '.');
                                elseif ($promo->tipe === 'fix_price')
                                    $promoText = "Harga Spesial Rp " . number_format($promo->value, 0, ',', '.');
                                elseif ($promo->tipe === 'hari_gratis')
                                    $promoText = "Gratis " . $promo->value . " Hari";
                                elseif ($promo->tipe === 'jam_gratis')
                                    $promoText = "Gratis " . $promo->value . " Jam";
                                elseif ($promo->tipe === 'cashback')
                                    $promoText = "Cashback Rp " . number_format($promo->value, 0, ',', '.');
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
