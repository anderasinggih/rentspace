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
        // Fetch active rentals containing iPhone units with their location history
        $rentals = Rental::with(['units' => function($q) {
            $q->whereHas('category', function($cq) {
                $cq->where('name', 'like', '%iphone%');
            });
        }, 'units.locations' => function($q) {
            $q->latest()->limit(50); // Fetch more points for route shadow
        }, 'units.category'])
        ->where('status', 'paid')
        ->where('waktu_mulai', '<=', now())
        ->where('waktu_selesai', '>=', now())
        ->get();

        // Map data for Leaflet
        $devices = [];
        foreach($rentals as $rental) {
            foreach($rental->units as $unit) {
                if($unit->category && str_contains(strtolower($unit->category->name), 'iphone')) {
                    $locs = $unit->locations;
                    $lastLoc = $locs->first();
                    
                    // Format history for polyline
                    $history = $locs->map(fn($l) => [$l->lat, $l->lng])->toArray();

                    $devices[] = [
                        'id' => $unit->id,
                        'seri' => $unit->seri,
                        'nama_peminjam' => $rental->nama,
                        'booking_code' => $rental->booking_code,
                        'lat' => $lastLoc ? $lastLoc->lat : null,
                        'lng' => $lastLoc ? $lastLoc->lng : null,
                        'battery' => $lastLoc ? $lastLoc->battery_level : null,
                        'last_seen' => $lastLoc ? $lastLoc->created_at->diffForHumans() : 'Unknown',
                        'status' => $rental->status,
                        'history' => $history // Added history array
                    ];
                }
            }
        }

        return view('livewire.admin.radar-devices', [
            'devices' => $devices
        ])->layout('layouts.admin');
    }
}
