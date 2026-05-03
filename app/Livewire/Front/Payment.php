<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Midtrans\Transaction;

class Payment extends Component
{
    public $rental;
    public $snapToken;
    public $metode_pembayaran = 'online';
    public $selectedChannel = null;
    public $paymentInfo = null;
    public $paymentFee = 0;
    public $paymentFeeLabel = '';

    public function boot()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function mount($booking_code)
    {
        $this->rental = Rental::with('units')
            ->where('booking_code', $booking_code)
            ->firstOrFail();

        // 1. Proteksi: Jika sudah 'LUNAS' atau 'DIBATALKAN', paksa ke halaman success
        if (in_array($this->rental->status, ['paid', 'cancelled'])) {
            return redirect()->route('public.success', $this->rental->booking_code);
        }

        // 2. Proteksi: Khusus 'CASH', paksa stay di struk (Success)
        if ($this->rental->metode_pembayaran === 'cash' && !request()->query('change')) {
            return redirect()->route('public.success', $this->rental->booking_code);
        }

        // 3. Reset: Jika user minta ganti metode
        if (request()->query('change')) {
            $this->rental->update([
                'payment_details' => null,
                'metode_pembayaran' => 'online'
            ]);
            $this->rental->refresh();
            
            // --- CUCI ALAMAT: Buang ?change=1 biar gak ngeriset terus pas direfresh ---
            return redirect()->route('public.payment', $this->rental->booking_code);
        }

        // 3. JEMPUT BOLA AWAL: Tanya Midtrans dulu (Prioritas Utama)
        $this->paymentInfo = $this->rental->payment_details;
        if ($this->rental->status === 'pending' && $this->rental->metode_pembayaran !== 'online' && !empty($this->paymentInfo)) {
            $this->checkStatus(); // Ini bakal nge-update status jadi paid kalau emang sudah lunas
            $this->rental->refresh();
        }

        // 4. GARIS POLISI: Baru cek apakah sudah basi (Hanya jika masih pending & BUKAN cash)
        $isExpired = (now()->timestamp - $this->rental->created_at->timestamp >= 900);
        if ($this->rental->status === 'pending' && $this->rental->metode_pembayaran !== 'cash' && $isExpired) {
            // --- JURUS SAPU JAGAT ---
            $banks = ['BCA', 'BRI', 'BNI', 'MANDIRI', 'PERMATA', 'BSI', 'CIMB', 'QRIS'];
            foreach ($banks as $bank) {
                try {
                    $potentialId = $this->rental->booking_code . '-' . $bank;
                    \Midtrans\Transaction::cancel($potentialId);
                } catch (\Exception $e) { }
            }
            $this->rental->update(['status' => 'cancelled']);
            return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
        }

        // 5. Load: Siapkan variabel layar
        // 4. Load: Ambil data dari DB kalau memang sudah pernah milih bank (Bukan sedang pemilihan/online)
        if ($this->rental->metode_pembayaran !== 'online') {
            $this->paymentInfo = $this->rental->payment_details;
            
            if ($this->paymentInfo) {
                $this->selectedChannel = $this->rental->metode_pembayaran;
                $this->paymentFee = data_get($this->paymentInfo, 'payment_fee', 0);
                $this->paymentFeeLabel = data_get($this->paymentInfo, 'payment_fee_label', '');
                
                // Sync status jika data masih mentah
                if (isset($this->paymentInfo['order_id']) && count($this->paymentInfo) <= 1) {
                    $this->checkStatus();
                }
            }
        } else {
            // PAKSA KOSONG kalau statusnya 'online' (User harus milih bank)
            $this->paymentInfo = null;
            $this->selectedChannel = null;
        }
    }

    public function checkStatus()
    {
        $this->rental = $this->rental->fresh();
        
        // 1. CEK MIDTRANS DULU (Prioritas Nomor Wahid)
        $orderId = data_get($this->rental->payment_details, 'order_id');
        if ($orderId && $this->rental->status === 'pending') {
            try {
                Config::$serverKey = config('midtrans.server_key');
                Config::$isProduction = config('midtrans.is_production');

                $status = (array) Transaction::status($orderId);
                $transactionStatus = $status['transaction_status'] ?? '';

                // --- OPTIMASI: Hanya tulis ke DB kalau ada berita penting ---
                $isStatusChanged = ($transactionStatus !== ($this->rental->payment_details['transaction_status'] ?? ''));
                $isEmptyDetails = empty($this->rental->payment_details);

                if ($isStatusChanged || $isEmptyDetails) {
                    $existingDetails = $this->rental->payment_details ?? [];
                    $updatedDetails = array_merge($existingDetails, $status);
                    $this->rental->update(['payment_details' => $updatedDetails]);
                    $this->rental = $this->rental->fresh();
                }
                
                $this->paymentInfo = $this->rental->payment_details;

                if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
                    $this->rental->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
                }

                if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                    $this->rental->update(['status' => 'cancelled']);
                    return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
                }
            } catch (\Exception $e) { }
        }

        // 2. CEK TIMER (Hanya jika di Midtrans memang belum dibayar & BUKAN cash)
        if ($this->rental->status === 'pending' && $this->rental->metode_pembayaran !== 'cash' && (now()->timestamp - $this->rental->created_at->timestamp >= 900)) {
            // --- JURUS SAPU JAGAT ---
            $banks = ['BCA', 'BRI', 'BNI', 'MANDIRI', 'PERMATA', 'BSI', 'CIMB', 'QRIS'];
            foreach ($banks as $bank) {
                try {
                    $potentialId = $this->rental->booking_code . '-' . $bank;
                    \Midtrans\Transaction::cancel($potentialId);
                } catch (\Exception $e) { }
            }

            $this->rental->update(['status' => 'cancelled']);
            return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
        }
    }

    public function selectChannel($channel)
    {
        if ($this->rental->status !== 'pending') return;

        // Setup Midtrans Config (WAJIB tiap kali action)
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
        
        $this->selectedChannel = $channel;
        $this->paymentInfo = null;
        $this->snapToken = null;

        $item_details = [];
        foreach ($this->rental->units as $unit) {
            $item_details[] = [
                'id' => 'UNIT-' . $unit->id,
                'price' => (int) ($unit->pivot->price_snapshot ?? $unit->harga_per_hari),
                'quantity' => 1,
                'name' => $unit->seri,
            ];
        }

        if ($this->rental->potongan_diskon > 0) {
            $item_details[] = [
                'id' => 'DISCOUNT', 'price' => -(int) $this->rental->potongan_diskon, 'quantity' => 1, 'name' => 'Potongan Diskon'
            ];
        }

        if ($this->rental->kode_unik_pembayaran > 0) {
            $item_details[] = [
                'id' => 'UNIQUE-CODE', 'price' => (int) $this->rental->kode_unik_pembayaran, 'quantity' => 1, 'name' => 'Kode Unik Pembayaran'
            ];
        }

        // Batalkan transaksi lama di Midtrans biar dashboard rapi (Status jadi Cancelled)
        // 1. Hitung Ulang Harga dengan Biaya Layanan Baru
        $baseTotal = ($this->rental->subtotal_harga - $this->rental->potongan_diskon) + $this->rental->kode_unik_pembayaran;
        
        $paymentFee = 0;
        $paymentFeeLabel = '';
        if ($channel === 'qris') {
            $paymentFee = floor($baseTotal * 0.007); // 0.7% untuk QRIS
            $paymentFeeLabel = '(0.7%)';
        } elseif (in_array($channel, ['bca', 'mandiri', 'bni', 'bri', 'permata', 'bsi', 'cimb'])) {
            $paymentFee = 4000; // Flat 4rb untuk Bank Transfer
            $paymentFeeLabel = '(Bank Fee)';
        }

        $newGrandTotal = $baseTotal + $paymentFee;
        $uniqueOrderId = $this->rental->booking_code . '-' . strtoupper($channel);
        
        $this->paymentFee = $paymentFee;
        $this->paymentFeeLabel = $paymentFeeLabel;

        $params = [
            'transaction_details' => [
                'order_id' => $uniqueOrderId,
                'gross_amount' => (int) $newGrandTotal,
            ],
            'customer_details' => [
                'first_name' => $this->rental->nama,
                'email' => $this->rental->nik . '@rentspace.com', // Dummy email as Core API often requires it
                'phone' => $this->rental->no_wa,
            ],
            'item_details' => array_merge($item_details, [
                [
                    'id' => 'payment_fee',
                    'price' => (int) $paymentFee,
                    'quantity' => 1,
                    'name' => 'Biaya Layanan (' . strtoupper($channel) . ')'
                ]
            ]),
        ];

        // LOGIKA BAYAR TUNAI (CASH)
        if ($channel === 'cash') {
            // Bayar di tempat tidak perlu kode unik
            $newGrandTotal = $this->rental->subtotal_harga - $this->rental->potongan_diskon;
            
            $paymentInfo = [
                'payment_type' => 'cash',
                'status_message' => 'Silakan lakukan pembayaran tunai di lokasi kami.',
                'address' => \App\Models\Setting::getVal('admin_address', 'Jl. Jend. Sudirman, Purwokerto')
            ];

            $this->rental->update([
                'metode_pembayaran' => 'cash',
                'kode_unik_pembayaran' => 0,
                'grand_total' => $newGrandTotal,
                'payment_details' => $paymentInfo
            ]);

            // Refresh data model agar state terbaru tersimpan di instance ini
            $this->rental->refresh();

            // Beri jeda singkat agar user bisa melihat proses loading
            usleep(1000000);

            // Redirect TANPA 'navigate: true' untuk memastikan transisi halaman bersih
            return redirect()->route('public.success', $this->rental->booking_code);
        }

        // LOGIKA BAYAR QRIS STATIS (MANUAL)
        if ($channel === 'manual_qris') {
            sleep(1);
            
            $paymentInfo = [
                'payment_type' => 'manual_qris',
                'status_message' => 'Silakan scan QRIS di bawah ini dan konfirmasi ke Admin.',
                'qris_image' => \App\Models\Setting::getVal('qris', 'default.jpg')
            ];

            $this->rental->update([
                'metode_pembayaran' => 'manual_qris',
                'kode_unik_pembayaran' => 0,
                'grand_total' => $baseTotal,
                'payment_details' => $paymentInfo
            ]);

            $this->paymentInfo = $paymentInfo;
            $this->selectedChannel = $channel;
            return;
        }

        // --- JURUS ANTI-DUPLICATE ---
        // Kita hanya ambil dari database kalau datanya sudah "Lengkap" (paling tidak ada nomor VA/QRIS-nya)
        $existingDetails = $this->rental->payment_details;
        $isExistingAndValid = isset($existingDetails['order_id']) && 
                             $existingDetails['order_id'] === $uniqueOrderId && 
                             (isset($existingDetails['va_numbers']) || isset($existingDetails['payment_code']) || isset($existingDetails['bill_key']));

        if ($isExistingAndValid) {
            $this->paymentInfo = $existingDetails;
            
            // --- KUNCI MATI BIAR GAK ILANG ---
            $this->paymentInfo['payment_fee'] = $paymentFee;
            $this->paymentInfo['payment_fee_label'] = $paymentFeeLabel;
            
            $this->rental->update([
                'metode_pembayaran' => $channel,
                'grand_total' => $newGrandTotal,
                'payment_details' => $this->paymentInfo // Simpan lagi biar permanen
            ]);
            
            $this->paymentFee = $paymentFee;
            $this->paymentFeeLabel = $paymentFeeLabel;
            return;
        }

        try {
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');

            $coreParams = $params;
            if (in_array($channel, ['bca', 'bni', 'bri', 'permata', 'bsi', 'cimb'])) {
                $coreParams['payment_type'] = 'bank_transfer';
                $coreParams['bank_transfer'] = ['bank' => $channel];
            } elseif ($channel === 'mandiri') {
                $coreParams['payment_type'] = 'echannel';
                $coreParams['echannel'] = ['bill_info1' => 'Rental Payment', 'bill_info2' => $this->rental->booking_code];
            } elseif ($channel === 'qris') {
                $coreParams['payment_type'] = 'qris';
            }

            // --- JURUS PAMUNGKAS: CEK DULU SEBELUM TEMBAK ---
            try {
                // 1. Tanya ke Midtrans: "ID ini sudah pernah dibuat belum?"
                $response = Transaction::status($uniqueOrderId);
                $this->paymentInfo = (array) $response;
                
                // --- SINKRON HARGA: Ikut kata Midtrans ---
                if (isset($this->paymentInfo['gross_amount'])) {
                    $newGrandTotal = (int) $this->paymentInfo['gross_amount'];
                }
            } catch (\Exception $e) {
                // 2. Kalau error (berarti ID memang belum pernah ada), baru kita BIKIN (Charge)
                $response = CoreApi::charge($coreParams);
                $this->paymentInfo = (array) $response;
                
                // Pastikan harga sinkron juga di sini
                if (isset($this->paymentInfo['gross_amount'])) {
                    $newGrandTotal = (int) $this->paymentInfo['gross_amount'];
                }
            }
            
            // --- JURUS CUCI DATA (Murni Array) ---
            // Kita cuci datanya biar bener-bener jadi array polos, biar Livewire gak bingung nangkepnya
            $this->paymentInfo = json_decode(json_encode($this->paymentInfo), true);

            // --- JURUS SEMEN BETON: Masukkan biaya layanan ke data yang disimpan ---
            $this->paymentInfo['payment_fee'] = $paymentFee;
            $this->paymentInfo['payment_fee_label'] = $paymentFeeLabel;

            // 1. Simpan ke Database (Lengkap dengan harga baru)
            $this->rental->update([
                'payment_details' => $this->paymentInfo, 
                'metode_pembayaran' => $channel,
                'grand_total' => $newGrandTotal // Simpan harga baru + biaya bank
            ]);
            
            // 2. PAKSA RE-ASSIGN (Sengat Listrik)
            $this->rental = $this->rental->fresh();
            $this->paymentInfo = $this->rental->payment_details;
            
            $this->paymentFee = $paymentFee;
            $this->paymentFeeLabel = $paymentFeeLabel;
            $this->selectedChannel = $channel;
            
        } catch (\Exception $e) {
            // LOG ERROR BIAR KELIHATAN DI LARAVEL.LOG
            \Log::error('Midtrans Core API Error: ' . $e->getMessage());
            
            // JIKA AKUN DIBLOKIR CORE API, ATAU KUNCI SALAH (SANDBOX VS PRODUCTION), PAKAI POPUP SEBAGAI PANCINGAN
            try {
                $snapParams = $params;
                $mapping = ['bca' => 'bca_va', 'mandiri' => 'echannel', 'bni' => 'bni_va', 'bri' => 'bri_va', 'permata' => 'permata_va', 'qris' => 'qris', 'bsi' => 'bsi_va', 'cimb' => 'cimb_va'];
                if (isset($mapping[$channel])) {
                    $snapParams['enabled_payments'] = [$mapping[$channel]];
                }

                $this->snapToken = Snap::getSnapToken($snapParams);
                $this->rental->update(['payment_details' => ['order_id' => $uniqueOrderId], 'metode_pembayaran' => $channel]);
                $this->dispatch('pay-with-snap', token: $this->snapToken);
            } catch (\Exception $e2) {
                \Log::error('Midtrans Snap Fallback Error: ' . $e2->getMessage());
                session()->flash('error', 'Gagal memulai pembayaran: ' . $e2->getMessage() . ' (Core API Error: ' . $e->getMessage() . ')');
            }
        }
    }

    public function resetPayment()
    {
        // Hitung harga dasar asli untuk mereset grand_total
        $baseTotal = ($this->rental->subtotal_harga - $this->rental->potongan_diskon) + $this->rental->kode_unik_pembayaran;

        $this->rental->update([
            'metode_pembayaran' => 'online',
            'status' => 'pending',
            'grand_total' => $baseTotal // Kembalikan ke harga dasar
        ]);
        $this->paymentInfo = null;
        $this->selectedChannel = null;
        $this->paymentFee = 0;
        $this->paymentFeeLabel = '';
        $this->snapToken = null;
    }

    public function finish($method = null)
    {
        return redirect()->route('public.success', $this->rental->booking_code);
    }

    public function cancelBooking()
    {
        // Hitung harga dasar asli untuk mereset grand_total
        $baseTotal = ($this->rental->subtotal_harga - $this->rental->potongan_diskon) + $this->rental->kode_unik_pembayaran;

        // Saat dibatalkan, reset juga metodenya biar gak 'menuduh' QRIS atau bank tertentu di struk
        $this->rental->update([
            'status' => 'cancelled',
            'metode_pembayaran' => 'online',
            'payment_details' => null,
            'grand_total' => $baseTotal // Kembalikan ke harga dasar
        ]);
        
        return redirect()->route('public.success', $this->rental->booking_code);
    }

    public function render()
    {
        // --- JURUS MATA DEWA (Anti-Ilang) ---
        // Kita paksa web nengok ke database detik ini juga sebelum nampil ke layar.
        // Biar biaya layanan nggak sempet kabur pas polling jalan.
        if ($this->rental->payment_details && $this->rental->metode_pembayaran !== 'online') {
            $this->paymentInfo = $this->rental->payment_details;
            $this->paymentFee = data_get($this->paymentInfo, 'payment_fee', 0);
            $this->paymentFeeLabel = data_get($this->paymentInfo, 'payment_fee_label', '');
        }

        return view('livewire.front.payment')->layout('layouts.app');
    }
}
