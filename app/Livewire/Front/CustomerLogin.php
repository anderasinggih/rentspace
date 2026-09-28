<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Masuk - RENT SPACE')]
class CustomerLogin extends Component
{
    public string $no_wa = '';
    public string $email = '';
    public bool $remember = false;

    protected $rules = [
        'no_wa' => 'required|string|min:8',
        'email' => 'required|email',
    ];

    protected $messages = [
        'no_wa.required' => 'Nomor WhatsApp wajib diisi.',
        'no_wa.min'      => 'Nomor WhatsApp minimal 8 karakter.',
        'email.required' => 'Email wajib diisi.',
        'email.email'    => 'Format email tidak valid.',
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

        $formattedWa = \App\Helpers\CustomerHelper::formatWa($this->no_wa);

        // Check if any rental exists with this WA AND Email
        $customer = Rental::where(function($q) use ($formattedWa) {
                $q->where('no_wa', $this->no_wa)
                  ->orWhere('no_wa', $formattedWa);
            })
            ->where('email', strtolower(trim($this->email)))
            ->first();

        if (!$customer) {
            $this->addError('no_wa', 'Data tidak ditemukan. Pastikan Nomor WA dan Email sesuai dengan yang didaftarkan saat booking.');
            return;
        }

        // Save customer session (24h if remember me, otherwise 6h)
        $duration = $this->remember ? 24 : 6;
        
        session()->put('customer_session', [
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
