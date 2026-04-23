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

        // 1. Prokteksi: Jika sudah 'LUNAS', paksa ke halaman success
        if ($this->rental->status === 'paid') {
            return redirect()->route('public.success', $this->rental->booking_code);
        }

        // 2. Proteksi: Khusus 'CASH', paksa stay di struk (Success)
        if ($this->rental->metode_pembayaran === 'cash' && !request()->query('change')) {
            return redirect()->route('public.success', $this->rental->booking_code);
        }

        // 3. Reset: Jika user minta ganti metode
        if (request()->query('change')) {
            // Cancel transaksi lama di Midtrans biar gak numpuk
            if ($this->rental->payment_details && isset($this->rental->payment_details['order_id'])) {
                try {
                    \Midtrans\Transaction::cancel($this->rental->payment_details['order_id']);
                } catch (\Exception $e) {}
            }

            $this->rental->update([
                'payment_details' => null,
                'metode_pembayaran' => 'online'
            ]);
            $this->rental->refresh();
        }

        // 4. Load: Ambil detail pembayaran Midtrans yang ada
        if ($this->rental->payment_details) {
            $this->paymentInfo = $this->rental->payment_details;
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
        if ($this->rental->status === 'paid') {
            return redirect()->route('public.success', $this->rental->booking_code);
        }

        $orderId = $this->paymentInfo['order_id'] ?? null;
        if ($orderId) {
            try {
                $status = (array) Transaction::status($orderId);
                
                // Simpan data VA/Biller Code ke database biar muncul di tampilan
                $this->paymentInfo = array_merge($this->rental->payment_details ?? [], $status);
                $this->rental->update(['payment_details' => $this->paymentInfo]);

                if ($status['transaction_status'] == 'settlement' || $status['transaction_status'] == 'capture') {
                    $this->rental->update(['status' => 'paid']);
                    return redirect()->route('public.success', $this->rental->booking_code);
                }
            } catch (\Exception $e) {}
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
        $oldOrderId = data_get($this->rental->payment_details, 'order_id');
        if ($oldOrderId) {
            try {
                \Midtrans\Transaction::cancel($oldOrderId);
                \Illuminate\Support\Facades\Log::info("Midtrans Cancel Success: " . $oldOrderId);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Midtrans Cancel Failed: " . $oldOrderId . " Error: " . $e->getMessage());
            }
        }

        // Gunakan suffix timestamp biar gak bentrok di Midtrans kalau gonta-ganti bank
        $uniqueOrderId = $this->rental->booking_code . '-' . time();
        
        $params = [
            'transaction_details' => [
                'order_id' => $uniqueOrderId,
                'gross_amount' => (int) $this->rental->grand_total,
            ],
            'customer_details' => [
                'first_name' => $this->rental->nama,
                'phone' => $this->rental->no_wa,
            ],
            'item_details' => $item_details,
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
        // Jika ada transaksi lama di Midtrans, coba batalkan dulu biar rapi
        if ($this->rental->payment_details && isset($this->rental->payment_details['order_id'])) {
            try {
                \Midtrans\Transaction::cancel($this->rental->payment_details['order_id']);
            } catch (\Exception $e) {
                // Abaikan jika gagal (misal: transaksinya memang belum terdaftar di Midtrans)
            }
        }

        $this->rental->update([
            'payment_details' => null,
            'metode_pembayaran' => 'online',
            'status' => 'pending'
        ]);
        $this->paymentInfo = null;
        $this->selectedChannel = 'online';
        $this->snapToken = null;
    }

    public function finish($method = null)
    {
        return redirect()->route('public.success', $this->rental->booking_code);
    }

    public function cancelBooking()
    {
        $this->rental->update(['status' => 'cancelled']);
        return redirect()->route('public.success', $this->rental->booking_code);
    }

    public function render()
    {
        return view('livewire.front.payment')->layout('layouts.app');
    }
}
