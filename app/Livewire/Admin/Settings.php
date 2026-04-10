<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    public $qris_photo;

    public function saveQris()
    {
        $this->validate([
            'qris_photo' => 'required|image|max:2048', // 2MB Max
        ]);

        $this->qris_photo->storeAs('public', 'qris.jpg');
        
        session()->flash('message', 'QRIS Photo successfully updated.');
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('layouts.admin');
    }
}
