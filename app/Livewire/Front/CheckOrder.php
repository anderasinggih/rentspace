<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Cek Pesanan - RENT SPACE')]
class CheckOrder extends Component
{
    public $nik = '';
    public $no_wa = '';
    public $orders = null;
    public $currentTab = 'pesanan';

    public function mount()
    {
        $customerSession = session('customer_session');
        if ($customerSession && isset($customerSession['expires_at']) && now()->timestamp < $customerSession['expires_at']) {
            $this->nik = $customerSession['nik'];
            $this->no_wa = $customerSession['no_wa'];
            $this->search();
        } else {
            return redirect()->route('customer.login');
        }
    }

    private function saveToSession()
    {
        session([
            'check_order_cache' => [
                'nik' => $this->nik,
                'no_wa' => $this->no_wa,
                'expires_at' => now()->addMinutes(5)->timestamp
            ]
        ]);

        // Also create/update persistent customer session (6 hours)
        session()->put('customer_session', [
            'nik' => $this->nik,
            'no_wa' => $this->no_wa,
            'logged_in_at' => now()->toISOString(),
            'expires_at' => now()->addHours(6)->timestamp,
        ]);
    }

    public function search()
    {
        $this->orders = Rental::with('units')
            ->where('nik', $this->nik)
            ->where('no_wa', $this->no_wa)
            ->latest()
            ->get();
    }

    public function getTotalOrdersProperty()
    {
        return $this->orders ? $this->orders->count() : 0;
    }

    public function getTotalBillingProperty()
    {
        if (!$this->orders)
            return 0;
        return $this->orders->whereIn('status', ['pending', 'paid', 'completed'])->sum('grand_total');
    }

    public function getActiveRentalsCountProperty()
    {
        if (!$this->orders)
            return 0;
        return $this->orders->where('status', 'paid')->filter(function ($order) {
            return now()->isBetween($order->waktu_mulai, $order->waktu_selesai);
        })->count();
    }

    public function cancelOrder($booking_code)
    {
        $order = Rental::where('booking_code', $booking_code)->first();
        if ($order && $order->status === 'pending') {
            $order->update(['status' => 'cancelled']);
            $this->search();
            session()->flash('success_cancel', 'Pesanan berhasil dibatalkan.');
        }
    }

    public function render()
    {
        return view('livewire.front.check-order');
    }
}
