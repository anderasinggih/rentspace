<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use App\Models\Setting;
use Livewire\Component;

class Success extends Component
{
    public $rental;
    public $tolerance;
    public $admin_wa;
    public $waUrl;
    public $isOwner = false;

    public function mount($booking_code)
    {
        $this->rental = Rental::with('units')
            ->where('booking_code', $booking_code)
            ->firstOrFail();
        $this->tolerance = (int) Setting::getVal('late_tolerance_minutes', 60);
        $this->admin_wa = Setting::getVal('admin_wa', '6281234567890');
        
        // Ownership check: only show actions/warnings to the person who just completed the booking
        $this->isOwner = in_array($booking_code, session('owned_bookings', []));

        $this->waUrl = $this->generateWaUrl();
    }

    public function validateOrder()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') return;
        
        $this->rental->update(['status' => 'paid']);
        $this->rental = $this->rental->fresh();
        session()->flash('admin_message', 'Pesanan berhasil divalidasi!');
    }

    public function cancelOrder()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') return;

        $this->rental->update(['status' => 'cancelled']);
        $this->rental = $this->rental->fresh();
        session()->flash('admin_message', 'Pesanan berhasil dibatalkan!');
    }

    private function generateWaUrl()
    {
        $orderId = str_pad($this->rental->id, 5, '0', STR_PAD_LEFT);
        $units = $this->rental->units->pluck('seri')->implode(', ');
        $total = number_format($this->rental->grand_total, 0, ',', '.');
        $mulai = $this->rental->waktu_mulai->translatedFormat('d M Y, H:i');
        $selesai = $this->rental->waktu_selesai->translatedFormat('d M Y, H:i');
        $link = route('public.success', $this->rental->booking_code);

        $text = "Halo Admin, saya baru saja melakukan pemesanan di *RENT SPACE*. Berikut rinciannya:\n\n" .
                "*ID Pesanan:* #{$orderId}\n" .
                "*Nama:* {$this->rental->nama}\n" .
                "*Unit:* {$units}\n" .
                "*Waktu Sewa:* {$mulai} s/d {$selesai}\n" .
                "*Total Bayar:* Rp {$total}\n";

        if ($this->rental->affiliate_code) {
            $text .= "*Ref:* {$this->rental->affiliate_code}\n";
        }

        $text .= "\nLink Detail: {$link}\n\n" .
                "Mohon bantuannya untuk diproses. Terima kasih!";

        return "https://wa.me/{$this->admin_wa}?text=" . urlencode($text);
    }

    public function render()
    {
        return view('livewire.front.success')->layout('layouts.app');
    }
}
