<?php

namespace App\Livewire\Front;

use Livewire\Component;

class CustomerLogout extends Component
{
    public function mount()
    {
        session()->forget('customer_session');
        session()->forget('check_order_cache');
        return redirect()->route('public.home');
    }

    public function render()
    {
        return view('livewire.front.customer-logout');
    }
}
