<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\Unit;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Device Radar - RentSpace')]
class RadarDevices extends Component
{
    public function render()
    {
        // Fetch active rentals containing iPhone units
        $rentals = Rental::with(['units' => function($q) {
            $q->whereHas('category', function($cq) {
                $cq->where('name', 'like', '%iphone%');
            });
        }, 'units.locations' => function($q) {
            $q->latest()->limit(1);
        }])
        ->where('status', 'paid')
        ->where('waktu_mulai', '<=', now())
        ->where('waktu_selesai', '>=', now())
        ->get();

        // Map data for Leaflet
        $devices = [];
        foreach($rentals as $rental) {
            foreach($rental->units as $unit) {
                if($unit->category && str_contains(strtolower($unit->category->name), 'iphone')) {
                    $lastLoc = $unit->locations->first();
                    $devices[] = [
                        'id' => $unit->id,
                        'seri' => $unit->seri,
                        'nama_peminjam' => $rental->nama,
                        'booking_code' => $rental->booking_code,
                        'lat' => $lastLoc ? $lastLoc->lat : null,
                        'lng' => $lastLoc ? $lastLoc->lng : null,
                        'battery' => $lastLoc ? $lastLoc->battery_level : null,
                        'last_seen' => $lastLoc ? $lastLoc->created_at->diffForHumans() : 'Unknown',
                        'status' => $rental->status
                    ];
                }
            }
        }

        return view('livewire.admin.radar-devices', [
            'devices' => $devices
        ])->layout('layouts.admin');
    }
}
