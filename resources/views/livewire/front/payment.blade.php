<div class="py-12 px-4 sm:px-6 lg:px-8 bg-muted/20 min-h-[calc(100vh-4rem)]">
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-background rounded-2xl shadow-sm border border-border p-6 sm:p-8 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground mb-4">Selesaikan Pembayaran</h1>
            <p class="text-muted-foreground mb-8">Tagihan Anda untuk unit <strong>{{ $rental->unit->seri }}</strong> telah dibuat. Mohon transfer tepat hingga tiga digit terakhir agar pembayaran dapat dikenali sistem / admin secara otomatis.</p>
            
            <div class="flex flex-col items-center justify-center p-8 bg-muted/30 border border-border rounded-xl">
                <!-- Toggle Menu -->
                <div class="bg-background border border-border p-1 rounded-lg inline-flex mb-8">
                    <button wire:click="$set('metode_pembayaran', 'qris')" class="px-6 py-2 rounded-md text-sm font-semibold transition-colors {{ $metode_pembayaran === 'qris' ? 'bg-primary text-primary-foreground shadow' : 'text-muted-foreground hover:text-foreground' }}">
                        Transfer QRIS
                    </button>
                    <button wire:click="$set('metode_pembayaran', 'cash')" class="px-6 py-2 rounded-md text-sm font-semibold transition-colors {{ $metode_pembayaran === 'cash' ? 'bg-primary text-primary-foreground shadow' : 'text-muted-foreground hover:text-foreground' }}">
                        Bayar Tunai (Cash)
                    </button>
                </div>

                <!-- Nominal Tagihan -->
                <p class="text-sm font-semibold text-muted-foreground mb-2">Total Transaksi</p>
                <div class="flex items-end justify-center tracking-tight mb-8">
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
                    <span class="text-2xl font-bold align-baseline mr-1 text-foreground">Rp</span>
                    <span class="text-5xl font-extrabold text-foreground">{{ number_format($basePrice, 0, ',', '.') }}</span>
                    @if($metode_pembayaran === 'qris')
                    <span class="text-5xl font-extrabold text-primary">.{{ $lastThree }}</span>
                    @else
                    <span class="text-5xl font-extrabold text-muted-foreground">.000</span>
                    @endif
                </div>

                @if($metode_pembayaran === 'qris')
                <!-- Dummy QRIS Box -->
                <div class="w-64 h-64 bg-background border-2 border-dashed border-border rounded-xl flex flex-col items-center justify-center shadow-sm relative overflow-hidden">
                    <span class="text-muted-foreground text-sm font-medium mb-3">Pindai QRIS ini</span>
                    <div class="w-48 h-48 bg-muted/50 rounded flex items-center justify-center pt-2 relative">
                        <img src="{{ asset('storage/qris.jpg') }}" onerror="this.style.display='none'" class="absolute inset-0 w-full h-full object-cover">
                        <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground/30 z-0"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><rect width="8" height="8" x="7" y="7"/><path d="M3 13h18"/><path d="M13 3v18"/></svg>
                    </div>
                </div>
                <div class="mt-8 text-sm text-muted-foreground max-w-sm">
                    Mohon transfer sesuai dengan tiga digit terakhir unik ({{ $lastThree }}) untuk verifikasi otomatis.
                </div>
                @else
                <!-- Cash Box -->
                <div class="w-64 p-6 bg-amber-500/10 border border-amber-500/20 rounded-xl flex flex-col items-center shadow-sm text-amber-700 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                    <span class="text-sm font-bold text-center">Silakan Lakukan Pembayaran Tunai</span>
                    <p class="text-xs text-center mt-2 opacity-80">Anda dapat membayar langsung di kasir kami saat pengambilan Unit.</p>
                </div>
                @endif
            </div>

            <button wire:click="finish" class="mt-8 w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-12 px-8 font-bold text-lg">
                {{ $metode_pembayaran === 'qris' ? 'Saya Sudah Transfer QRIS' : 'Selesaikan Pesanan Tunai' }}
            </button>
        </div>
    </div>
</div>
