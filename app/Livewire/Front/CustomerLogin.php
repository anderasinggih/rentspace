<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Masuk - RENT SPACE')]
class CustomerLogin extends Component
{
    public string $identifier = '';
    public bool $remember = false;

    protected $rules = [
        'identifier' => 'required|string|min:8',
    ];

    protected $messages = [
        'identifier.required' => 'NIK atau Nomor WhatsApp wajib diisi.',
        'identifier.min'      => 'Input minimal 8 karakter.',
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

        // Check if any rental exists with this identifier matching either NIK or No. WA
        $customer = Rental::where('nik', $this->identifier)
            ->orWhere('no_wa', $this->identifier)
            ->first();

        if (!$customer) {
            $this->addError('identifier', 'Data tidak ditemukan. Pastikan NIK atau Nomor WA sesuai dengan yang didaftarkan saat booking.');
            return;
        }

        // Save customer session (24h if remember me, otherwise 6h)
        $duration = $this->remember ? 24 : 6;
        
        session()->put('customer_session', [
            'nik'        => $customer->nik,
            'no_wa'      => $customer->no_wa,
            'nama'       => $customer->nama,
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
