<?php

namespace App\Livewire\Affiliate;

use App\Models\User;
use App\Models\AffiliatorProfile;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Register extends Component
{
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $nik;
    public $no_hp;
    public $alamat;
    public $bank_name;
    public $bank_account_number;
    public $bank_account_name;

    public function mount()
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('affiliate.dashboard');
        }
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'nik' => 'required|string|unique:affiliator_profiles,nik',
        'no_hp' => 'required|string',
        'alamat' => 'required|string',
        'bank_name' => 'required|string',
        'bank_account_number' => 'required|string',
        'bank_account_name' => 'required|string',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'affiliator',
        ]);

        AffiliatorProfile::create([
            'user_id' => $user->id,
            'nik' => $this->nik,
            'no_hp' => $this->no_hp,
            'alamat' => $this->alamat,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            'status' => 'pending',
        ]);

        Auth::login($user);

        return redirect()->route('affiliate.dashboard');
    }

    public function render()
    {
        return view('livewire.affiliate.register')->layout('layouts.app', [
            'title' => 'Daftar Affiliator',
            'hideNavbar' => false
        ]);
    }
}
