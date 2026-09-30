{{-- Catalog / Pricelist Section --}}
<div class="mt-8 mb-12 w-full">
    <div x-data="{ visible: false }" x-intersect.once="visible = true"
        :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
        class="text-center mb-12 mt-4 transition-all duration-1000 ease-out">
        <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Katalog Harga Sewa</h2>
        <p class="mt-4 text-sm sm:text-base text-muted-foreground">Pilih unit terbaik yang sesuai dengan kebutuhan dan budget Anda.</p>
    </div>

    @php
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
                    <h3 class="text-base sm:text-lg font-bold text-foreground whitespace-nowrap px-4 py-1.5 bg-card rounded-full border border-border shadow-xs">
                        @if($category && $category->icon)
                            <span class="mr-1.5">{{ $category->icon }}</span>
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
                            class="group relative bg-card text-card-foreground border border-border rounded-xl p-3.5 shadow-sm hover:border-primary/60 hover:shadow-md transition-all duration-200 flex-col justify-between overflow-hidden">
                            
                            <div class="relative z-10">
                                <h4 class="font-bold text-foreground text-sm group-hover:text-primary transition-colors leading-tight mb-1">
                                    {{ $unit->seri }}
                                </h4>
                                <div class="mt-1 space-y-0.5">
                                    @if($isIphone)
                                        <p class="text-xs text-muted-foreground">{{ $unit->warna }} · {{ $unit->memori }}</p>
                                    @elseif($unit->specs)
                                        <div class="flex flex-wrap gap-x-1.5 gap-y-0.5 mt-1">
                                            @foreach($unit->specs as $key => $val)
                                                @if($val)
                                                    <span class="text-[10px] bg-secondary/50 px-1.5 py-0.5 rounded text-secondary-foreground">
                                                        <span class="font-bold opacity-70">{{ $key }}:</span> {{ $val }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($unit->kondisi && !$isIphone)
                                        <p class="text-[10px] text-muted-foreground italic mt-0.5 line-clamp-1">{{ $unit->kondisi }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 pt-2 border-t border-border">
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-[9px] text-muted-foreground uppercase font-medium leading-none mb-1">Sewa / Hari</span>
                                        <div class="flex items-baseline gap-0.5">
                                            <span class="text-[10px] font-semibold text-muted-foreground">Rp</span>
                                            <span class="text-sm font-bold text-foreground">{{ number_format($unit->harga_per_hari, 0, ',', '.') }}</span>
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
                            class="inline-flex items-center gap-2 text-xs font-semibold text-muted-foreground hover:text-foreground transition-colors py-2 px-5 rounded-full border border-border bg-card shadow-xs">
                            <span x-text="expanded ? 'Sembunyikan' : 'Lihat Lebih Lengkap (+' + {{ $units->count() - 4 }} + ')'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200" :class="{ 'rotate-180': expanded }">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
