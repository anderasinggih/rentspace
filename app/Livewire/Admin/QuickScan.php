<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Rental;
use App\Models\Unit;

class QuickScan extends Component
{
    public $scannedUnit = null;
    public $activeRental = null;

    public function findUnit($id)
    {
        $this->scannedUnit = Unit::with('category')->find($id);
        
        if ($this->scannedUnit) {
            // Cek apakah ada penyewaan aktif yang menyertakan unit ini
            // Di sistem ini, Rental ADALAH Transaksi/Booking
            $this->activeRental = Rental::whereHas('units', function($q) use ($id) {
                    $q->where('units.id', $id);
                })
                ->whereIn('status', ['paid', 'confirmed', 'active', 'pending'])
                ->where('waktu_selesai', '>=', now())
                ->latest()
                ->first();
        } else {
            $this->activeRental = null;
            session()->flash('error', 'Unit tidak ditemukan!');
        }
    }

    public function confirmHandover($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $rental = Rental::findOrFail($id);
        
        if (in_array($rental->status, ['paid', 'confirmed'])) {
            $rental->update(['status' => 'active']);
            $this->findUnit($this->scannedUnit->id);
            session()->flash('message', 'Serah terima unit BERHASIL! Unit sekarang dalam status SEWA.');
        }
    }

    public function confirmReturn($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $rental = Rental::findOrFail($id);
        
        if ($rental->status === 'active') {
            $rental->update(['status' => 'completed']);
            $this->findUnit($this->scannedUnit->id);
            session()->flash('message', 'Pengembalian unit BERHASIL! Transaksi telah SELESAI.');
        }
    }

    public function resetScan()
    {
        $this->scannedUnit = null;
        $this->activeRental = null;
    }

    public function render()
    {
        return view('livewire.admin.quick-scan')->layout('layouts.admin');
    }
}
