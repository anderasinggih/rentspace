<?php

namespace App\Livewire\Affiliate;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $email;
    public $password;
    public $remember = true;

    public function mount()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('affiliate.dashboard');
        }
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            
            return redirect()->route('affiliate.dashboard');
        }

        $this->addError('email', 'Kombinasi email dan password salah.');
    }

    public function render()
    {
        return view('livewire.affiliate.login')->layout('layouts.app', [
            'title' => 'Login Affiliator',
            'hideNavbar' => false
        ]);
    }
}
