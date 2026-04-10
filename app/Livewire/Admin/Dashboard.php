<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\Unit;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $totalUnits = Unit::count();
        $activeUnits = Unit::where('is_active', true)->count();
        
        $todayRentals = Rental::whereDate('created_at', Carbon::today())->count();
        $pendingRentals = Rental::where('status', 'pending')->count();
        
        $totalRevenue = Rental::where('status', 'completed')
            ->sum('grand_total');

        $activeRentals = Rental::with('unit')->whereIn('status', ['paid', 'pending'])
            ->where('waktu_mulai', '<=', now())
            ->where('waktu_selesai', '>=', now())
            ->get();

        return view('livewire.admin.dashboard', compact(
            'totalUnits', 'activeUnits', 'todayRentals', 'pendingRentals', 'totalRevenue', 'activeRentals'
        ))->layout('layouts.admin');
    }
}
