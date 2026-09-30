{{-- Hero Section (Apple / Ziggs Minimalist Showcase Design) --}}
<section class="relative w-full pt-12 pb-6 sm:pt-20 sm:pb-10 overflow-hidden text-foreground">
    {{-- Dynamic Greeting Badge (Top Position) --}}
    @if(\App\Models\Setting::getVal('is_greeting_active', '1') == '1')
        <div class="w-full flex justify-center mb-6 px-4">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-secondary/80 border border-border text-foreground/80 shadow-xs">
                <span class="inline-block w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <p class="text-xs font-medium tracking-normal">{{ $greeting }}</p>
            </div>
        </div>
    @endif

    <!-- Hero Text Content -->
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex flex-col items-center">
        @if($isCustomerLoggedIn && $closestActiveRental)
            @php
                $selesaiHeroTimestamp = $closestActiveRental->waktu_selesai->timestamp * 1000;
            @endphp
            <div x-data="{
                    countdown: '',
                    status: 'green',
                    endTime: {{ $selesaiHeroTimestamp }},
                    tick() {
                        const now = Date.now();
                        const diff = Math.floor((this.endTime - now) / 1000);
                        if (diff <= 0) { 
                            this.countdown = 'Selesai'; 
                            this.status = 'red';
                            return; 
                        }

                        const hoursTotal = diff / 3600;
                        if (hoursTotal < 3) {
                            this.status = 'red';
                        } else if (hoursTotal < 6) {
                            this.status = 'amber';
                        } else {
                            this.status = 'green';
                        }

                        const h = Math.floor(hoursTotal);
                        const m = Math.floor((diff % 3600) / 60);
                        const s = diff % 60;

                        if(h > 0) {
                            this.countdown = h + 'j ' + m + 'm ' + s + 'd';
                        } else {
                            this.countdown = m + 'm ' + s + 'd';
                        }
                    }
                }" x-init="tick(); setInterval(() => tick(), 1000)"
                class="mb-6 flex flex-col items-center">
                <span class="text-[11px] font-semibold uppercase tracking-wider mb-2 flex items-center gap-1.5 px-3 py-1 rounded-full border shadow-xs"
                    :class="{
                        'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400': status === 'green',
                        'bg-amber-500/10 border-amber-500/20 text-amber-600 dark:text-amber-400': status === 'amber',
                        'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400': status === 'red'
                    }">
                    <span class="w-2 h-2 rounded-full animate-ping"
                        :class="{
                            'bg-emerald-500': status === 'green',
                            'bg-amber-500': status === 'amber',
                            'bg-rose-500': status === 'red'
                        }"></span>
                    <span x-text="status === 'red' ? 'Waktu Hampir Habis' : 'Sisa Durasi Rental Anda'"></span>
                </span>
                <div x-text="countdown" class="text-4xl sm:text-6xl font-black font-mono tracking-tight text-foreground"></div>
            </div>
        @endif

        {{-- Pill Tagline --}}
        <div class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3.5 py-1 text-xs font-semibold text-primary mb-6 shadow-xs tracking-wide">
            <span>RENT SPACE PURWOKERTO</span>
        </div>

        {{-- Main Headline --}}
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-foreground leading-[1.12]">
            {!! nl2br(e(\App\Models\Setting::getVal('home_title', "Sewa iPhone Impian Anda Lebih Mudah & Cepat"))) !!}
        </h1>

        {{-- Subdescription --}}
        <p class="mt-4 sm:mt-6 text-sm sm:text-lg leading-relaxed text-muted-foreground font-normal max-w-2xl">
            {{ \App\Models\Setting::getVal('home_description', 'Pilihan terbaik untuk merasakan pengalaman menggunakan produk Apple original tanpa harus membeli baru. Bebas atur jadwal sewa, harga bersahabat, tanpa syarat ribet!') }}
        </p>

        {{-- CTA Buttons (Apple Style: Pill Buttons with System Blue & Clean WA) --}}
        <div class="mt-8 flex flex-row items-center justify-center gap-3 sm:gap-4 w-full sm:w-auto">
            <!-- Button SEWA SEKARANG -->
            <a href="{{ route('public.booking') }}" wire:navigate
                data-haptic="success"
                class="inline-flex items-center justify-center rounded-full font-semibold transition-all bg-primary hover:bg-primary/90 text-primary-foreground shadow-sm hover:shadow active:scale-[0.98] h-11 sm:h-12 px-6 sm:px-8 text-xs sm:text-sm tracking-normal">
                Sewa Sekarang
            </a>
            
            <!-- Button WHATSAPP -->
            @php
                $adminWa = \App\Models\Setting::getVal('admin_wa', '6281229509087');
                $adminWaClean = preg_replace('/[^0-9]/', '', $adminWa);
                if (str_starts_with($adminWaClean, '0')) {
                    $adminWaClean = '62' . substr($adminWaClean, 1);
                }
            @endphp
            <a href="https://wa.me/{{ $adminWaClean }}?text={{ urlencode('Halo kak, saya mau tanya rental iPhone di RentSpace.') }}"
                target="_blank"
                data-haptic="medium"
                class="inline-flex items-center justify-center rounded-full font-semibold transition-all bg-[#25D366] hover:bg-[#20ba59] text-white shadow-sm hover:shadow active:scale-[0.98] h-11 sm:h-12 px-6 sm:px-8 text-xs sm:text-sm tracking-normal">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 shrink-0"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
                WhatsApp
            </a>
        </div>

        <!-- Hero Placement Announcement -->
        <div class="w-full mt-6">
            <livewire:front.global-announcement placement="hero" />
        </div>
    </div>

    <!-- Apple-Style Product Showcase Frame (Banner Container) -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 sm:mt-14"
        x-data="{ 
            activeSlide: 0,
            slides: [
                '/uploads/{{ \App\Models\Setting::getVal('hero', 'default.jpg') }}?t={{ time() }}',
                '/uploads/{{ \App\Models\Setting::getVal('hero2', 'default2.jpg') }}?t={{ time() }}',
                '/uploads/{{ \App\Models\Setting::getVal('hero3', 'default3.jpg') }}?t={{ time() }}'
            ],
            init() {
                setInterval(() => {
                    this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                }, 5000);
            }
        }">
        <div class="relative w-full aspect-[16/8] sm:aspect-[21/9] rounded-2xl sm:rounded-3xl border border-border bg-muted/40 shadow-sm overflow-hidden">
            @for($i = 0; $i < 3; $i++)
                @php
                    $key = $i == 0 ? 'hero' : 'hero' . ($i + 1);
                    $image = \App\Models\Setting::getVal($key, 'default.jpg');
                @endphp
                <div class="absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out"
                     :class="activeSlide === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                    <img src="/uploads/{{ $image }}" 
                         alt="Rent Space Banner {{ $i + 1 }}"
                         class="w-full h-full object-cover"
                         onerror="this.style.display='none'">
                </div>
            @endfor

            {{-- Carousel Dot Indicators --}}
            <div class="absolute bottom-3 sm:bottom-4 inset-x-0 z-20 flex justify-center items-center gap-1.5">
                @for($i = 0; $i < 3; $i++)
                    <button 
                        @click="activeSlide = {{ $i }}"
                        class="h-1.5 transition-all duration-300 rounded-full"
                        :class="activeSlide === {{ $i }} ? 'w-6 bg-primary' : 'w-1.5 bg-foreground/30 hover:bg-foreground/50'"
                        aria-label="Slide {{ $i + 1 }}">
                    </button>
                @endfor
            </div>
        </div>
    </div>
</section>
