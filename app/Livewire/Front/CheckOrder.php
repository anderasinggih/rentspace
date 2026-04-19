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

    protected $rules = [
        'nik' => 'required|string|min:10',
        'no_wa' => 'required|string|min:10',
    ];

    public function mount()
    {
        // 1. Check URL parameters first
        $urlNik = request('nik');
        $urlWa = request('no_wa');

        if ($urlNik && $urlWa) {
            $this->nik = $urlNik;
            $this->no_wa = $urlWa;
            $this->saveToSession();
            $this->search();
            return;
        }

        // 2. Check Session with 5-minute expiry
        $cached = session('check_order_cache');
        if ($cached && isset($cached['expires_at']) && now()->timestamp < $cached['expires_at']) {
            $this->nik = $cached['nik'];
            $this->no_wa = $cached['no_wa'];
            $this->search();
        }
    }

    private function saveToSession()
    {
        session(['check_order_cache' => [
            'nik' => $this->nik,
            'no_wa' => $this->no_wa,
            'expires_at' => now()->addMinutes(5)->timestamp
        ]]);
    }

    public function search()
    {
        $this->validate();
        
        // Refresh session timer on every successful search
        $this->saveToSession();

        $this->orders = Rental::with('units')
            ->where('nik', $this->nik)
            ->where('no_wa', $this->no_wa)
            ->latest()
            ->get();

        if ($this->orders->isEmpty()) {
            session()->flash('error', 'Pesanan tidak ditemukan. Pastikan NIK dan Nomor WA sudah benar.');
        }
    }

    public function resetSearch()
    {
        session()->forget('check_order_cache');
        $this->reset(['nik', 'no_wa', 'orders']);
    }

    public function render()
    {
        return view('livewire.front.check-order');
    }
}
