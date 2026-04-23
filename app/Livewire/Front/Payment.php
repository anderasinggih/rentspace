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
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
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
        }

        // 4. Load: Ambil detail pembayaran Midtrans yang ada
        if ($this->rental->payment_details) {
            $this->paymentInfo = $this->rental->payment_details;
            $this->paymentFee = data_get($this->paymentInfo, 'payment_fee', 0);
            $this->paymentFeeLabel = data_get($this->paymentInfo, 'payment_fee_label', '');
            $this->selectedChannel = $this->rental->metode_pembayaran;
            
            // Sync status jika data masih mentah
            if (isset($this->paymentInfo['order_id']) && count($this->paymentInfo) <= 1) {
                $this->checkStatus();
            }
        }
    }

    public function checkStatus()
    {
        $this->rental = $this->rental->fresh();
        
        // 1. Cek status lokal di database
        if ($this->rental->status === 'paid') {
            return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
        }

        // 2. JEMPUT BOLA: Tanya langsung ke API Midtrans
        $orderId = data_get($this->rental->payment_details, 'order_id');
        
        if ($orderId) {
            try {
                // Pastikan konfigurasi terpasang (jaga-jaga jika di lingkungan polling berbeda)
                Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);

                $status = (array) Transaction::status($orderId);
                $transactionStatus = $status['transaction_status'] ?? '';

                // Simpan detail terbaru dari Midtrans ke database kita (biar sinkron)
                $updatedDetails = array_merge($this->rental->payment_details ?? [], $status);
                $this->rental->update(['payment_details' => $updatedDetails]);

                if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
                    // Jika Webhook gagal/telat, baris ini jadi pahlawannya!
                    $this->rental->update(['status' => 'paid']);
                    return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
                }

                if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                    $this->rental->update(['status' => 'cancelled']);
                    return $this->redirect(route('public.success', $this->rental->booking_code), navigate: true);
                }
            } catch (\Exception $e) {
                // Jika error (misal API Midtrans down), biarkan polling berikutnya mencoba lagi
            }
        }
    }

    public function selectChannel($channel)
    {
        if ($this->rental->status === 'paid') return;

        // Setup Midtrans Config (WAJIB tiap kali action)
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
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
        $uniqueOrderId = $this->rental->booking_code . '-' . time();
        $this->rental->update([
            'grand_total' => $newGrandTotal,
            'payment_details' => [
                'order_id' => $uniqueOrderId,
                'payment_fee' => $paymentFee,
                'payment_fee_label' => $paymentFeeLabel,
                'base_total' => $baseTotal
            ],
            'metode_pembayaran' => $channel
        ]);
        
        $this->rental->refresh();
        $this->paymentInfo = $this->rental->payment_details;
        $this->paymentFee = $paymentFee;
        $this->paymentFeeLabel = $paymentFeeLabel;

        $params = [
            'transaction_details' => [
                'order_id' => $uniqueOrderId,
                'gross_amount' => (int) $newGrandTotal,
            ],
            'customer_details' => [
                'first_name' => $this->rental->nama,
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
            sleep(1); // Delay 1 detik biar gercep
            
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

            return redirect()->route('public.success', $this->rental->booking_code);
        }

        try {
            // Coba Tampilan Sendiri dulu
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

            $response = CoreApi::charge($coreParams);
            $this->paymentInfo = (array) $response;
            $this->rental->update(['payment_details' => $this->paymentInfo, 'metode_pembayaran' => $channel]);
            
        } catch (\Exception $e) {
            // JIKA AKUN DIBLOKIR CORE API, PAKAI POPUP SEBAGAI PANCINGAN
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
                session()->flash('error', 'Gagal memulai pembayaran: ' . $e2->getMessage());
            }
        }
    }

    public function resetPayment()
    {
        // Hitung harga dasar asli untuk mereset grand_total
        $baseTotal = ($this->rental->subtotal_harga - $this->rental->potongan_diskon) + $this->rental->kode_unik_pembayaran;

        $this->rental->update([
            'payment_details' => null,
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
        return view('livewire.front.payment')->layout('layouts.app');
    }
}
