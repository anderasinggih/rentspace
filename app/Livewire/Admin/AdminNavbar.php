<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdminNavbar extends Component
{
    public $showScanner = false;

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }

    public function toggleScanner()
    {
        $this->showScanner = !$this->showScanner;
    }

    public function render()
    {
        return view('livewire.admin.admin-navbar');
    }
}
