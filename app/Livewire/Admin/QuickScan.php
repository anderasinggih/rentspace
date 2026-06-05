<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Rental;
use App\Models\Unit;

class QuickScan extends Component
{
    use \App\Traits\LogsStaffActivity;

    public $scannedUnit = null;
    public $activeRental = null;

    public function findUnit($id)
    {
        $this->scannedUnit = Unit::with('category')->find($id);
        
        if ($this->scannedUnit) {
            // Cek apakah ada penyewaan aktif yang menyertakan unit ini
            // Di sistem ini, Rental ADALAH Transaksi/Booking
            // Kita hapus filter waktu_selesai agar unit telat tetap bisa di-scan untuk pengobatan
            $this->activeRental = Rental::whereHas('units', function($q) use ($id) {
                    $q->where('units.id', $id);
                })
                ->whereIn('status', ['paid', 'confirmed', 'renting', 'pending'])
                // Prioritaskan status 'renting', lalu urutkan berdasarkan waktu mulai terdekat
                ->orderByRaw("FIELD(status, 'renting', 'paid', 'confirmed', 'pending')")
                ->orderBy('waktu_mulai', 'asc')
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
            $rental->update(['status' => 'renting', 'handed_over_at' => now()]);
            
            $this->logActivity('handover_unit', $rental, "Validasi ambil unit untuk transaksi #{$rental->id} (via QuickScan)");
            
            $this->findUnit($this->scannedUnit->id);
            session()->flash('message', 'Validasi ambil unit BERHASIL! Unit sekarang dalam status RENT.');
        }
    }

    public function confirmReturn($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $rental = Rental::findOrFail($id);
        
        if ($rental->status === 'renting') {
            $rental->update(['status' => 'completed', 'completed_at' => now()]);
            
            $this->logActivity('complete_rental', $rental, "Pengembalian unit untuk transaksi #{$rental->id} (via QuickScan)");
            
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
