<?php

namespace App\Livewire\Front;

use Livewire\Component;
use App\Models\Setting;

class About extends Component
{
    public $faq_items = [];

    public function mount()
    {
        $savedFaq = Setting::getVal('about_faq_items', json_encode([]));
        $this->faq_items = json_decode($savedFaq, true) ?: [];
    }

    public function render()
    {
        return view('livewire.front.about')->layout('layouts.app');
    }
}
