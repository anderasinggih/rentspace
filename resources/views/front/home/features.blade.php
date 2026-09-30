{{-- Cara Order & Testimonials Section --}}

<!-- Cara Order Section -->
<div class="mt-8 mb-20">
    <div x-data="{ visible: false }" x-intersect.once="visible = true"
        :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
        class="text-center mb-12 transition-all duration-1000">
        <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Cara Order di Rent Space</h2>
        <p class="mt-4 text-sm sm:text-base text-muted-foreground">Proses mudah dan cepat, hanya butuh beberapa menit.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
        @php
            $steps = [
                [
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>',
                    'title' => 'Pilih Unit & Waktu',
                    'desc' => 'Pilih seri unit yang Anda inginkan dan tentukan durasi sewa yang sesuai kebutuhan.'
                ],
                [
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>',
                    'title' => 'Lengkapi Data',
                    'desc' => 'Isi data diri seperti Nama dan WhatsApp dengan benar untuk proses verifikasi cepat.'
                ],
                [
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
                    'title' => 'Bayar Pesanan',
                    'desc' => 'Bayar aman via Transfer, QRIS, atau langsung di lokasi (Cash on Delivery).'
                ],
                [
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
                    'title' => 'Ambil di Lokasi',
                    'desc' => 'Datang ke lokasi kami untuk pengambilan unit sesuai jadwal yang telah ditentukan.'
                ],
            ];
        @endphp
        @foreach($steps as $i => $step)
            <div x-data="{ visible: false }" x-intersect.once="visible = true"
                :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                style="transition-delay: {{ $i * 150 }}ms"
                class="relative p-4 sm:p-6 rounded-xl sm:rounded-2xl border bg-card hover:border-primary/50 transition-all duration-300 group">
                <div class="absolute top-2 right-3 sm:top-4 sm:right-4 text-2xl sm:text-4xl font-black text-primary/10 sm:text-primary/10 group-hover:text-primary/20 transition-colors">0{{ $i + 1 }}</div>
                <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-lg sm:rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-4 sm:mb-6 group-hover:scale-110 transition-transform">
                    {!! $step['icon'] !!}
                </div>
                <h3 class="text-sm sm:text-lg font-bold mb-1.5 sm:mb-2 line-clamp-1 sm:line-clamp-none">{{ $step['title'] }}</h3>
                <p class="text-[10px] sm:text-sm text-muted-foreground leading-relaxed line-clamp-3 sm:line-clamp-none">{{ $step['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>

<!-- Testimonials Section -->
@php
    $realFeedbacks = collect();
    if (\Illuminate\Support\Facades\Schema::hasColumn('rentals', 'rating')) {
        $realFeedbacks = \App\Models\Rental::whereNotNull('rating')
            ->where('rating', '>=', 4)
            ->whereNotNull('feedback')
            ->where('feedback', '!=', '')
            ->latest()
            ->take(10)
            ->get();
    }
@endphp

@if($realFeedbacks->count() > 0)
<div x-data="{ visible: false }" x-intersect.once="visible = true"
    :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'"
    class="mt-16 mb-20 transition-all duration-1000 ease-out">
    <div class="text-center mb-10">
        <h2 class="text-2xl font-extrabold tracking-tight text-foreground sm:text-4xl">Kata Mereka</h2>
        <p class="mt-2 text-sm sm:text-base text-muted-foreground">Pengalaman terbaik bersama Rent Space</p>
    </div>

    <div class="relative overflow-hidden py-4">
        <!-- Infinite Marquee Row -->
        <div class="flex flex-nowrap gap-6 animate-marquee whitespace-nowrap mb-6">
            @php
                $loopCount = $realFeedbacks->count() <= 2 ? 6 : ($realFeedbacks->count() <= 5 ? 3 : 2);
                $finalFeedbacks = [];
                for($i=0; $i<$loopCount; $i++) { 
                    foreach($realFeedbacks as $fb) { $finalFeedbacks[] = $fb; }
                }
            @endphp
            @foreach($finalFeedbacks as $item)
                <div class="group relative inline-block w-[260px] sm:w-[320px] bg-card border border-border p-4 sm:p-6 rounded-2xl transition-all duration-300 hover:border-primary/50 hover:shadow-lg overflow-hidden">
                    <div class="relative z-10 flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-[10px] sm:text-[12px] font-bold text-primary">
                            {{ substr($item->nama, 0, 2) }}
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs font-bold text-foreground leading-tight group-hover:text-primary transition-colors">{{ $item->nama }}</p>
                            <div class="flex gap-0.5 mt-0.5">
                                @for($s=1; $s<=5; $s++)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="{{ $item->rating >= $s ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2.5" class="{{ $item->rating >= $s ? 'text-amber-500' : 'text-muted-foreground/30' }} sm:w-2.5 sm:h-2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                @endfor
                            </div>
                        </div>
                    </div>
                    <p class="relative z-10 text-[11px] sm:text-xs text-muted-foreground leading-relaxed whitespace-normal line-clamp-3">"{{ $item->feedback }}"</p>
                </div>
            @endforeach
        </div>

        <!-- Fade Gradients -->
        <div class="absolute inset-y-0 left-0 w-24 sm:w-32 bg-gradient-to-r from-background to-transparent z-10 pointer-events-none"></div>
        <div class="absolute inset-y-0 right-0 w-24 sm:w-32 bg-gradient-to-l from-background to-transparent z-10 pointer-events-none"></div>
    </div>
</div>

<style>
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .animate-marquee {
        display: flex;
        width: max-content;
        animation: marquee 40s linear infinite;
    }
</style>
@endif
