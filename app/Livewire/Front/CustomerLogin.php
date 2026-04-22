<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Masuk - RENT SPACE')]
class CustomerLogin extends Component
{
    public string $nik = '';
    public string $no_wa = '';
    public bool $remember = false;

    protected $rules = [
        'nik'   => 'required|string|min:10',
        'no_wa' => 'required|string|min:10',
    ];

    protected $messages = [
        'nik.required'   => 'NIK wajib diisi.',
        'nik.min'        => 'NIK minimal 10 karakter.',
        'no_wa.required' => 'Nomor WhatsApp wajib diisi.',
        'no_wa.min'      => 'Nomor WhatsApp minimal 10 karakter.',
    ];

    public function mount()
    {
        // If already logged in, redirect to home
        if (session('customer_session')) {
            return redirect()->route('public.home');
        }
    }

    public function login()
    {
        $this->validate();

        // Check if any rental exists with this NIK + No. WA combination
        $exists = Rental::where('nik', $this->nik)
            ->where('no_wa', $this->no_wa)
            ->exists();

        if (!$exists) {
            $this->addError('nik', 'NIK dan Nomor WA tidak ditemukan. Pastikan data sesuai dengan yang dimasukkan saat booking.');
            return;
        }

        // Save customer session (24h if remember me, otherwise 6h)
        $duration = $this->remember ? 24 : 6;
        
        session()->put('customer_session', [
            'nik'        => $this->nik,
            'no_wa'      => $this->no_wa,
            'logged_in_at' => now()->toISOString(),
            'expires_at' => now()->addHours($duration)->timestamp,
        ]);

        return redirect()->route('public.check-order');
    }

    public function render()
    {
        return view('livewire.front.customer-login')->layout('layouts.app');
    }
}
