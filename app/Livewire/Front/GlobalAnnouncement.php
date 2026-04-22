<?php

namespace App\Livewire\Front;

use App\Models\Announcement;
use Livewire\Component;

class GlobalAnnouncement extends Component
{
    public $placement = 'top';

    public function render()
    {
        return view('livewire.front.global-announcement', [
            'activeAnnouncement' => Announcement::active()->latest()->first()
        ]);
    }
}
