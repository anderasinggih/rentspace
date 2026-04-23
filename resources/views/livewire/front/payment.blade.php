<div class="py-0 px-4 sm:py-16 flex flex-col items-center font-sans tracking-normal" @if($rental->status === 'pending') wire:poll.5s="checkStatus" @endif>
    <!-- Processing Loading Overlay -->
    <div wire:loading wire:target="selectChannel" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex flex-col items-center w-full animate-in fade-in duration-300 px-6">
        <div class="bg-card p-5 rounded-2xl shadow-2xl flex flex-row items-center gap-4 text-left max-w-[320px] w-full border border-border/50 mt-[40vh]">
            <div class="relative h-10 w-10 flex shrink-0 items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-primary/20"></div>
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary border-t-transparent shadow-[0_0_15px_rgba(var(--primary),0.3)]"></div>
            </div>
            <div class="flex flex-col">
                <p class="text-sm font-bold text-foreground tracking-tight leading-none">Memproses Transaksi</p>
                <p class="text-[10px] text-muted-foreground mt-1.5 leading-tight">Mohon tunggu sebentar, kami sedang menyiapkan pesanan Anda...</p>
            </div>
        </div>
    </div>

    <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-sm overflow-hidden mt-4 animate-in fade-in duration-500">
        
        <!-- Header -->
        <div class="p-4 text-center border-b border-border/50 bg-muted/10">
            <h1 class="text-lg font-bold tracking-tight text-foreground">
                {{ $paymentInfo ? 'Selesaikan Pesanan' : 'Pilih Pembayaran' }}
            </h1>
            
            @if(session()->has('error'))
                <div class="mt-2 p-2 bg-red-500/10 border border-red-500/20 rounded-lg text-[10px] text-red-600">
                    {{ session('error') }}
                </div>
            @endif
            
            @php
                $isCash = data_get($paymentInfo, 'payment_type') === 'cash';
                
                // Ambil expiry_time dari Midtrans (prioritas)
                $expiryTime = data_get($paymentInfo, 'expiry_time');
                
                if ($expiryTime) {
                    // Jika ada expiry_time dari Midtrans, parse dengan asumsi WIB (UTC+7)
                    $expiryTimestamp = \Carbon\Carbon::parse($expiryTime, 'Asia/Jakarta')->timestamp * 1000;
                } else {
                    // Jika belum ada/masih pilih metode, default 24 jam dari buat
                    $expiryTimestamp = $rental->created_at->addDay()->timestamp * 1000;
                }
            @endphp

            @if($isCash)
                <div class="mt-3 flex flex-col items-center">
                    <span class="text-[9px] text-muted-foreground uppercase font-bold tracking-widest">Jadwal Pengambilan</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-emerald-600 tracking-tighter">
                            {{ $rental->waktu_mulai->format('H:i') }}
                        </span>
                        <span class="text-xs font-semibold text-muted-foreground">
                            {{ $rental->waktu_mulai->format('d M Y') }}
                        </span>
                    </div>
                </div>
            @else
                <div x-data="{
                    timeLeft: '',
                    status: 'green',
                    endTime: {{ $expiryTimestamp }},
                    update() {
                        const now = new Date().getTime();
                        const diff = this.endTime - now;
                        if (diff <= 0) { this.timeLeft = 'Waktu habis'; this.status = 'red'; return; }
                        const h = Math.floor(diff / (1000 * 60 * 60));
                        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const s = Math.floor((diff % (1000 * 60)) / 1000);
                        this.timeLeft = (h > 0 ? h + 'j ' : '') + m + 'm ' + s + 'd';
                        this.status = h < 1 ? 'red' : (h < 12 ? 'amber' : 'green');
                    }
                }" x-init="update(); setInterval(() => update(), 1000)" class="mt-2 flex flex-col items-center">
                    <span class="text-[9px] text-muted-foreground mb-0.5 uppercase font-bold tracking-widest">Batas Waktu Bayar</span>
                    <div x-text="timeLeft" 
                        class="text-3xl font-black font-mono tracking-tighter transition-all duration-500"
                        :class="{
                            'text-emerald-600': status === 'green',
                            'text-amber-500': status === 'amber',
                            'text-red-500 animate-pulse': status === 'red'
                        }">
                    </div>
                </div>
            @endif
        </div>

        <div class="p-4 space-y-4">
            @if(!$isCash)
                <!-- Summary -->
                <div class="p-4 bg-muted/30 rounded-xl border border-border/50 shadow-inner">
                    @if($paymentFee > 0)
                        <div class="flex justify-between text-muted-foreground text-[10px] mb-2 leading-none uppercase font-bold">
                            <span>Biaya Layanan <span class="text-zinc-500 font-medium ml-1">{{ $paymentFeeLabel }}</span></span>
                            <span class="font-bold text-foreground">+ Rp {{ number_format($paymentFee, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center pt-2 mt-1 border-t border-dashed border-border/60">
                        <span class="font-bold text-sm text-muted-foreground">Total Tagihan</span>
                        <span class="text-xl font-black text-foreground leading-none">Rp {{ number_format($rental->grand_total, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endif

            @if(!$paymentInfo)
                <!-- Bank List -->
                <div class="grid grid-cols-1 gap-2">
                    @php
                        $savedPayment = \App\Models\Setting::getVal('payment_methods', '[]');
                        $activeMethods = json_decode($savedPayment, true) ?: [];
                        
                        $banks = [
                            ['id' => 'bca', 'name' => 'BCA', 'sub' => 'Transfer otomatis', 'icon' => 'BCA', 'color' => 'bg-blue-500/10 text-blue-600 border-blue-500/20'],
                            ['id' => 'mandiri', 'name' => 'Mandiri', 'sub' => 'Mandiri bill', 'icon' => 'MDR', 'color' => 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20'],
                            ['id' => 'bni', 'name' => 'BNI', 'sub' => 'Transfer otomatis', 'icon' => 'BNI', 'color' => 'bg-orange-500/10 text-orange-600 border-orange-500/20'],
                            ['id' => 'bri', 'name' => 'BRI', 'sub' => 'Transfer otomatis', 'icon' => 'BRI', 'color' => 'bg-cyan-500/10 text-cyan-600 border-cyan-500/20'],
                            ['id' => 'permata', 'name' => 'Permata', 'sub' => 'Transfer bank', 'icon' => 'PRM', 'color' => 'bg-violet-500/10 text-violet-600 border-violet-500/20'],
                            ['id' => 'bsi', 'name' => 'BSI', 'sub' => 'Transfer otomatis', 'icon' => 'BSI', 'color' => 'bg-teal-500/10 text-teal-600 border-teal-500/20'],
                            ['id' => 'cimb', 'name' => 'CIMB', 'sub' => 'Transfer otomatis', 'icon' => 'CMB', 'color' => 'bg-red-500/10 text-red-600 border-red-500/20'],
                            ['id' => 'qris', 'name' => 'QRIS', 'sub' => 'Gopay / ShopeePay / QR', 'icon' => 'QR', 'color' => 'bg-fuchsia-500/10 text-fuchsia-600 border-fuchsia-500/20'],
                            ['id' => 'cash', 'name' => 'Bayar di Tempat', 'sub' => 'Tunai / Cash di Lokasi', 'icon' => 'CSH', 'color' => 'bg-zinc-500/10 text-zinc-600 border-zinc-500/20'],
                        ];
                        
                        // Filter hanya bank yang dicentang di Admin Settings
                        $filteredBanks = collect($banks)->filter(function($bank) use ($activeMethods) {
                            return isset($activeMethods[$bank['id']]) && $activeMethods[$bank['id']] == true;
                        })->all();
                    @endphp

                    @foreach($filteredBanks as $bank)
                        <button wire:click="selectChannel('{{ $bank['id'] }}')" wire:loading.attr="disabled"
                            class="group flex items-center p-3.5 rounded-xl border border-border bg-background hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-all text-left relative overflow-hidden">
                            <div class="w-10 h-10 rounded-lg {{ $bank['color'] }} border flex items-center justify-center mr-4 text-[10px] font-bold transition-all group-hover:scale-110">
                                {{ $bank['icon'] }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold leading-none mb-1">{{ $bank['name'] }}</p>
                                <p class="text-[11px] text-muted-foreground">{{ $bank['sub'] }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all"><path d="m9 18 6-6-6-6"/></svg>
                            
                            <div wire:loading wire:target="selectChannel('{{ $bank['id'] }}')" class="absolute inset-0 bg-background/80 flex items-center justify-center rounded-xl">
                                <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @else
                <!-- Detail View -->
                <div class="space-y-5 animate-in fade-in slide-in-from-bottom-2">
                    <div class="p-5 bg-muted/40 rounded-xl border border-border/50 text-center text-sm">
                        @if($selectedChannel === 'qris')
                            <p class="font-semibold text-muted-foreground mb-4">Silakan scan kode QRIS</p>
                            @if(isset($paymentInfo['actions']))
                                @php $qrAction = collect($paymentInfo['actions'])->where('name', 'generate-qr-code')->first(); @endphp
                                @if($qrAction)
                                    <div class="inline-block p-4 bg-white rounded-xl border border-border shadow-xl">
                                        <img src="{{ $qrAction['url'] }}" alt="QRIS" class="w-56 h-56 mx-auto">
                                    </div>
                                @endif
                            @endif
                        @else
                            <div class="space-y-4">
                                @if(data_get($paymentInfo, 'payment_type') === 'cash')
                                    <div class="space-y-4">
                                        <div class="p-3 bg-muted/50 border border-border rounded-xl text-center">
                                            <p class="text-[11px] font-bold text-foreground mb-0.5">Bayar Tunai di Lokasi</p>
                                            <p class="text-[10px] text-muted-foreground leading-tight">
                                                Silakan datang ke alamat kami untuk melakukan pembayaran.
                                            </p>
                                        </div>

                                        <div class="p-3 bg-background border border-border rounded-xl">
                                            <p class="text-[9px] font-bold text-muted-foreground mb-1 uppercase">Lokasi</p>
                                            <p class="text-[11px] font-medium text-foreground leading-tight">
                                                {{ data_get($paymentInfo, 'address') }}
                                            </p>
                                        </div>

                                        <!-- Struk Detail -->
                                        <div class="p-3 border-2 border-dashed border-muted rounded-xl bg-muted/10 space-y-2">
                                            <div class="flex justify-between items-start border-b border-muted pb-1.5">
                                                <div class="flex-1 pr-2">
                                                    <p class="text-[9px] font-bold text-muted-foreground uppercase">Unit</p>
                                                    <p class="text-[11px] font-bold leading-tight">{{ $rental->units->pluck('seri')->join(', ') }}</p>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <p class="text-[9px] font-bold text-muted-foreground uppercase text-emerald-600">Kode Booking</p>
                                                    <p class="text-[12px] font-black tracking-tight text-emerald-600">{{ $rental->booking_code }}</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex justify-between items-center opacity-80 text-[10px] py-0.5">
                                                <span class="font-medium text-muted-foreground uppercase tracking-tighter">Durasi Sewa</span>
                                                <span class="font-bold">{{ $rental->waktu_mulai->diffInDays($rental->waktu_selesai) }} Hari</span>
                                            </div>
                                            
                                            <div class="space-y-0.5 text-[11px]">
                                                <div class="flex justify-between opacity-80">
                                                    <span>Subtotal</span>
                                                    <span>{{ number_format($rental->subtotal_harga, 0, ',', '.') }}</span>
                                                </div>
                                                @if($rental->potongan_diskon > 0)
                                                    <div class="flex justify-between text-emerald-600 font-medium">
                                                        <span>Diskon</span>
                                                        <span>-{{ number_format($rental->potongan_diskon, 0, ',', '.') }}</span>
                                                    </div>
                                                @endif
                                                @if($rental->kode_unik_pembayaran > 0)
                                                    <div class="flex justify-between opacity-80">
                                                        <span>Kode Unik</span>
                                                        <span>+{{ number_format($rental->kode_unik_pembayaran, 0, ',', '.') }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex justify-between items-center border-t border-dashed border-muted pt-1.5 text-emerald-600 font-bold">
                                                <span class="text-[10px] uppercase">Total Tunai</span>
                                                <span class="text-lg tracking-tighter">Rp {{ number_format($rental->grand_total, 0, ',', '.') }}</span>
                                            </div>
                                        </div>

                                        @php
                                            $unitNames = $rental->units->pluck('seri')->join(', ');
                                            $startTime = $rental->waktu_mulai->format('d M Y, H:i');
                                            $endTime = $rental->waktu_selesai ? $rental->waktu_selesai->format('d M Y, H:i') : '-';
                                            
                                            $waMessage = "Halo Admin, saya baru saja melakukan pemesanan di RENT SPACE. Berikut rinciannya:\n\n"
                                                       . "Kode Booking: " . $rental->booking_code . "\n"
                                                       . "Nama: " . $rental->nama . "\n"
                                                       . "Unit: " . $unitNames . "\n"
                                                       . "Waktu Sewa: " . $startTime . " s/d " . $endTime . "\n"
                                                       . "Total Bayar: Rp " . number_format($rental->grand_total, 0, ',', '.') . "\n"
                                                       . "Ref: " . ($rental->affiliate_code ?: '-') . "\n\n"
                                                       . "Link Detail: " . route('public.payment', $rental->booking_code) . "\n\n"
                                                       . "Mohon bantuannya untuk diproses. Terima kasih!";
                                            
                                            $waUrl = "https://wa.me/" . \App\Models\Setting::getVal('admin_wa') . "?text=" . urlencode($waMessage);
                                        @endphp

                                        <a href="{{ $waUrl }}" 
                                           target="_blank"
                                           class="w-full h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center gap-2 font-bold border border-emerald-500/10 transition-all text-xs">
                                             <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                             Konfirmasi WhatsApp
                                         </a>
                                    </div>
                                @else
                                    <div class="text-center mb-5">
                                        <h3 class="text-3xl font-black text-foreground uppercase tracking-tighter">{{ $selectedChannel }}</h3>
                                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest opacity-80">Nomor Virtual Account</p>
                                    </div>
                                    <div class="p-3 bg-background border border-border rounded-xl flex items-center justify-between shadow-sm">
                                        <p class="text-base font-bold text-foreground break-all mr-2" id="va-number">
                                            @if($va = data_get($paymentInfo, 'va_numbers.0.va_number'))
                                                {{ $va }}
                                            @elseif($bk = data_get($paymentInfo, 'bill_key'))
                                                {{ $bk }}
                                            @elseif($pva = data_get($paymentInfo, 'permata_va_number'))
                                                {{ $pva }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                        <button onclick="copyVA()" class="h-8 w-8 flex items-center justify-center border border-border rounded-lg bg-background hover:bg-accent transition-all active:scale-90 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                        </button>
                                    </div>
                                    </div>
                                @endif
                                
                                @if($selectedChannel === 'mandiri')
                                    <div class="mt-3 p-3 bg-background border border-border rounded-xl flex justify-between items-center text-xs">
                                        <span class="text-muted-foreground font-medium">Biller Code</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold">{{ data_get($paymentInfo, 'biller_code', '-') }}</span>
                                            <button onclick="copyText('{{ data_get($paymentInfo, 'biller_code', '') }}')" class="h-6 w-6 flex items-center justify-center border border-border rounded-md bg-background hover:bg-accent transition-all active:scale-90">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                </div>
            @endif

            @if($paymentInfo)
                <div class="mt-3">
                    <button wire:click="resetPayment" class="mt-16 w-full h-11 rounded-xl bg-white text-zinc-900 flex items-center justify-center gap-2 font-bold border border-zinc-200 hover:bg-zinc-50 transition-all text-sm active:scale-95 shadow-sm">
                        Ubah Metode Pembayaran
                    </button>
                </div>
            @endif

            <div class=" text-center">
            <button wire:click="cancelBooking" wire:confirm="Batalkan pesanan ini?"
                class="text-xs font-bold text-red-600 hover:text-red-700 transition-colors">
                Batalkan transaksi
            </button>
        </div>
            
            <p class="text-[10px] text-muted-foreground text-center opacity-40">
                &copy; {{ date('Y') }} Rent Space &bull; Transaksi Terenkripsi SSL
            </p>
        </div>
    </div>

    <!-- Scripts Area -->
    @php
        $isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        $snapUrl = $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
    @endphp
    <script src="{{ $snapUrl }}" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
    <script>
        document.addEventListener('livewire:init', () => {
           Livewire.on('pay-with-snap', (event) => {
               window.snap.pay(event.token, {
                   onSuccess: function(result){ @this.call('finish'); },
                   onPending: function(result){ window.location.reload(); },
                   onClose: function(){ window.location.reload(); },
                   onError: function(result){ window.location.reload(); }
               });
           });
        });

        function copyVA() {
            var vaText = document.getElementById("va-number").innerText;
            navigator.clipboard.writeText(vaText);
            alert("Nomor VA berhasil disalin");
        }
    </script>
</div>