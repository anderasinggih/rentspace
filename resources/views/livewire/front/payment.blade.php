<div class="py-4 sm:py-12 px-3 sm:px-6 lg:px-8 bg-background min-h-[calc(100vh-4rem)]">

    <div class="max-w-2xl mx-auto">
        <div class="bg-background rounded-2xl shadow-sm border border-border p-4 sm:p-8 text-center">
            
            <!-- Progress Bar -->
            <div class="mb-8 border-b border-border pb-4 text-left">
                <div class="flex items-center justify-between text-[10px] sm:text-xs font-medium mb-2 px-1">
                    <span class="text-primary font-bold">Pilih Unit</span>
                    <span class="text-primary font-bold">Data & Promo</span>
                    <span class="text-primary font-bold">Konfirmasi</span>
                    <span class="text-primary font-bold">Pembayaran</span>
                </div>
                <div class="h-2 bg-muted rounded-full overflow-hidden">
                    <div class="h-full bg-primary transition-all duration-500 rounded-full w-full"></div>
                </div>
            </div>

            <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight text-foreground mb-1 sm:mb-4">Selesaikan
                Pembayaran</h1>
            <p class="text-xs sm:text-sm text-muted-foreground mb-3 sm:mb-8">Tagihan untuk unit
                <strong>{{ $rental->units->pluck('seri')->implode(', ') ?: ($rental->unit->seri ?? '-') }}</strong>.
                Mohon transfer tepat hingga tiga digit terakhir agar pembayaran dapat dikenali sistem.</p>

            <div
                class="flex flex-col items-center justify-center p-4 sm:p-8 bg-muted/30 border border-border rounded-xl gap-3 sm:gap-0">

                <!-- ButtonGroup-style Payment Toggle -->
                @php
                    $enabledMethods = json_decode(\App\Models\Setting::getVal('payment_methods', json_encode(['qris' => true, 'cash' => true, 'transfer' => false])), true) ?: ['qris' => true, 'cash' => true];
                    $methodLabels = ['qris' => 'QRIS', 'cash' => 'Bayar Tunai', 'transfer' => 'Transfer Bank'];
                    $activeMethods = array_keys(array_filter($enabledMethods));
                    $count = count($activeMethods);
                @endphp
                @if($count > 0)
                    <div class="inline-flex rounded-md shadow-sm sm:mb-6 mx-2 sm:mx-0 flex-wrap justify-center"
                        role="group">
                        @foreach($activeMethods as $i => $method)
                            @php
                                $isFirst = $i === 0;
                                $isLast = $i === $count - 1;
                                $radius = $isFirst && $isLast ? 'rounded-md' : ($isFirst ? 'rounded-l-md' : ($isLast ? 'rounded-r-md' : ''));
                                $border = $isFirst ? 'border border-input' : 'border border-l-0 border-input';
                                $active = $metode_pembayaran === $method;
                            @endphp
                            <button wire:click="$set('metode_pembayaran', '{{ $method }}')"
                                class="{{ $radius }} {{ $border }} h-8 sm:h-9 px-4 sm:px-5 text-xs sm:text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2
                                    {{ $active ? 'bg-primary text-primary-foreground z-10' : 'bg-background text-foreground hover:bg-muted' }}">
                                {{ $methodLabels[$method] ?? strtoupper($method) }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Nominal Tagihan -->
                <p class="text-xs font-semibold text-muted-foreground mb-0.5 sm:mb-2">Total Transaksi</p>
                <div class="flex items-end justify-center tracking-tight mb-2 sm:mb-6">
                    @php
                        $grandTotalStr = (string) floor($rental->grand_total);
                        if (strlen($grandTotalStr) <= 3) {
                            $basePrice = 0;
                            $lastThree = str_pad($grandTotalStr, 3, '0', STR_PAD_LEFT);
                        } else {
                            $lastThree = substr($grandTotalStr, -3);
                            $basePrice = (float) substr($grandTotalStr, 0, -3);
                        }
                    @endphp
                    <span class="text-lg sm:text-2xl font-bold align-baseline mr-1 text-foreground">Rp</span>
                    <span
                        class="text-3xl sm:text-5xl font-extrabold text-foreground">{{ number_format($basePrice, 0, ',', '.') }}</span>
                    @if($metode_pembayaran === 'qris')
                        <span class="text-3xl sm:text-5xl font-extrabold text-primary">.{{ $lastThree }}</span>
                    @else
                        <span class="text-3xl sm:text-5xl font-extrabold text-muted-foreground">.000</span>
                    @endif
                </div>

                @if($metode_pembayaran === 'qris')
                    <!-- QRIS Box -->
                    <div
                        class="w-[220px] h-[220px] sm:w-64 sm:h-64 bg-background border-2 border-dashed border-border rounded-xl overflow-hidden shadow-sm relative">
                        <img src="{{ asset('uploads/' . \App\Models\Setting::getVal('qris', 'default.jpg')) }}"
                            onerror="this.style.display='none'" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                class="text-muted-foreground/30">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                <rect width="8" height="8" x="7" y="7" />
                                <path d="M3 13h18" />
                                <path d="M13 3v18" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-muted-foreground max-w-xs">
                        Transfer sesuai tiga digit unik (<strong class="text-primary">{{ $lastThree }}</strong>) untuk
                        verifikasi otomatis.
                    </div>
                @elseif($metode_pembayaran === 'transfer')
                    <!-- Transfer Bank Box -->
                    <div
                        class="w-full max-w-xs p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl flex flex-col items-center shadow-sm text-blue-700 dark:text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="mb-2">
                            <rect width="20" height="14" x="2" y="5" rx="2" />
                            <line x1="2" x2="22" y1="10" y2="10" />
                        </svg>
                        <span class="text-sm font-bold text-center">Transfer ke Rekening</span>
                        @php $adminWa = \App\Models\Setting::getVal('admin_wa', ''); @endphp
                        <p class="text-xs text-center mt-1 opacity-80">Konfirmasi via
                            WhatsApp{{ $adminWa ? ' ke ' . $adminWa : '' }} setelah transfer.</p>
                    </div>
                @else
                    <!-- Cash Box -->
                    <div
                        class="w-full max-w-xs p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex flex-col items-center shadow-sm text-amber-700 dark:text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="mb-2">
                            <rect width="20" height="12" x="2" y="6" rx="2" />
                            <circle cx="12" cy="12" r="2" />
                            <path d="M6 12h.01M18 12h.01" />
                        </svg>
                        <span class="text-sm font-bold text-center">Pembayaran Tunai</span>
                        <p class="text-xs text-center mt-1 opacity-80">Bayar langsung di kasir saat pengambilan Unit.</p>
                    </div>
                @endif
            </div>

            <!-- Submit Button with Spinner -->
            <button wire:click="finish" wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed"
                class="mt-4 sm:mt-8 w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 sm:h-12 px-8 font-bold text-base sm:text-lg transition-opacity">

                <svg wire:loading wire:target="finish" class="animate-spin h-5 w-5 text-primary-foreground"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>

                <span wire:loading wire:target="finish" class="text-sm">Memproses...</span>
                <span wire:loading.remove wire:target="finish">
                    {{ $metode_pembayaran === 'qris' ? 'Saya Sudah Transfer QRIS' : ($metode_pembayaran === 'transfer' ? 'Konfirmasi Transfer Bank' : 'Selesaikan Pesanan Tunai') }}
                </span>
            </button>

            <!-- Cancellation Option -->
            <div class="mt-8 pt-6 border-t border-border">
                <p class="text-[10px] text-muted-foreground mb-2">Ingin membatalkan pesanan ini?</p>
                <button wire:click="cancelBooking" 
                    wire:confirm="Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan."
                    class="mt-2 w-full inline-flex items-center justify-center rounded-md bg-red-600 text-white shadow hover:bg-red-700 h-10 sm:h-12 px-8 font-bold text-base sm:text-lg transition-opacity">
                    Batalkan Pesanan
                </button>
            </div>
        </div>
    </div>
</div>