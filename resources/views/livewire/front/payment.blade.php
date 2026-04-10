<div class="min-h-screen pt-24 pb-12 px-4 sm:px-6 lg:px-8 bg-muted/20">
    <livewire:navbar />
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-background rounded-2xl shadow-sm border border-border p-6 sm:p-8 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground mb-4">Selesaikan Pembayaran</h1>
            <p class="text-muted-foreground mb-8">Tagihan Anda untuk unit <strong>{{ $rental->unit->seri }}</strong> telah dibuat. Mohon transfer tepat hingga tiga digit terakhir agar pembayaran dapat dikenali sistem / admin secara otomatis.</p>
            
            <div class="flex flex-col items-center justify-center p-8 bg-muted/30 border border-border rounded-xl">
                <!-- Nominal Tagihan -->
                <p class="text-sm font-semibold text-muted-foreground mb-2">Total Transaksi</p>
                <div class="flex items-end justify-center tracking-tight mb-8">
                    @php
                       $grandTotalStr = (string)$rental->grand_total;
                       $lastThree = substr($grandTotalStr, -3);
                       $basePrice = substr($grandTotalStr, 0, -3);
                    @endphp
                    <span class="text-2xl font-bold align-baseline mr-1 text-foreground">Rp</span>
                    <span class="text-5xl font-extrabold text-foreground">{{ number_format($basePrice, 0, ',', '.') }}</span>
                    <span class="text-5xl font-extrabold text-primary">.{{ $lastThree }}</span>
                </div>

                <!-- Dummy QRIS or Rekening Box -->
                <div class="w-64 h-64 bg-background border-2 border-dashed border-border rounded-xl flex flex-col items-center justify-center shadow-sm relative overflow-hidden">
                    <span class="text-muted-foreground text-sm font-medium mb-3">Pindai QRIS ini</span>
                    <div class="w-48 h-48 bg-muted/50 rounded flex items-center justify-center pt-2">
                        <!-- Simulated QR Code placeholder -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground/30"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><rect width="8" height="8" x="7" y="7"/><path d="M3 13h18"/><path d="M13 3v18"/></svg>
                    </div>
                </div>

                <div class="mt-8 text-sm text-muted-foreground max-w-sm">
                    Setelah melakukan pembayaran menggunakan QRIS, silakan tekan tombol di bawah ini.
                </div>
            </div>

            <button wire:click="finish" class="mt-8 w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-12 px-8 font-bold text-lg">
                Saya Sudah Transfer
            </button>
        </div>
    </div>
</div>
