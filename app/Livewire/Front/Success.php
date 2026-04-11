<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use App\Models\Setting;
use Livewire\Component;

class Success extends Component
{
    public $rental;
    public $tolerance;

    public function mount($id)
    {
        $this->rental = Rental::with('unit')->findOrFail($id);
        $this->tolerance = (int) Setting::getVal('late_tolerance_minutes', 60);
    }

    public function render()
    {
        return view('livewire.front.success')->layout('layouts.app');
    }
}
