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
        ->get();

        // Map data for Leaflet
        $devices = [];
        foreach($rentals as $rental) {
            foreach($rental->units as $unit) {
                if($unit->category && str_contains(strtolower($unit->category->name), 'iphone')) {
                    $locs = $unit->locations; // Already limited to 50 in 'with'
                    $lastLoc = $locs->first();
                    
                    // Sort history from Oldest to Newest to draw a connected line in order
                    $history = $locs->reverse()->map(fn($l) => [$l->lat, $l->lng])->values()->toArray();

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
                        'history' => $history,
                        'is_overdue' => $rental->waktu_selesai < now(),
                        'time_left' => $rental->waktu_selesai > now() 
                            ? now()->diff($rental->waktu_selesai)->format('%dh %im') 
                            : '+ ' . $rental->waktu_selesai->diff(now())->format('%dh %im'),
                        'time_left_human' => $rental->waktu_selesai->diffForHumans()
                    ];
                }
            }
        }

        // Sort devices: overdue first, then by earliest end time
        usort($devices, function($a, $b) {
            if ($a['is_overdue'] && !$b['is_overdue']) return -1;
            if (!$a['is_overdue'] && $b['is_overdue']) return 1;
            return 0;
        });

        return view('livewire.admin.radar-devices', [
            'devices' => $devices
        ])->layout('layouts.admin');
    }
}
