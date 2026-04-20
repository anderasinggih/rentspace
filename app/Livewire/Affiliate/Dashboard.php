<?php

namespace App\Livewire\Affiliate;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public $tab = 'overview'; // overview, payouts, promos, profile
    
    // Profile Fields
    public $bank_name, $bank_account_number, $bank_account_name, $no_hp, $alamat, $nik;
    public $name, $email;

    // Password Fields
    public $new_password, $new_password_confirmation;

    // Statistics
    public $greeting;
    public $completedRentalsCount = 0;
    public $totalCommissionEarned = 0;
    public $totalWithdrawn = 0;
    public $profileCompleteness = 0;

    public $unitSearch = '';

    public function getWalletBalanceProperty()
    {
        $user = Auth::user();
        if (!$user) return 0;
        
        $profile = $user->affiliateProfile;
        return (float) ($profile->balance ?? 0);
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'affiliator') {
            return redirect()->route('admin.dashboard');
        }

        $profile = $user->affiliateProfile;
        $this->name = $user->name;
        $this->email = $user->email;

        if ($profile) {
            $this->bank_name = $profile->bank_name;
            $this->bank_account_number = $profile->bank_account_number;
            $this->bank_account_name = $profile->bank_account_name;
            $this->no_hp = $profile->no_hp;
            $this->alamat = $profile->alamat;
            $this->nik = $profile->nik;
            
            // Calculate Stats
            $this->totalCommissionEarned = $user->commissions()->sum('amount');
            $this->totalWithdrawn = $user->payouts()->where('status', 'processed')->sum('amount');
            $this->completedRentalsCount = $user->affiliateRentals()->where('status', 'completed')->count();
            
            // Calculate Profile Completeness
            $fields = ['bank_name', 'bank_account_number', 'bank_account_name', 'no_hp', 'alamat', 'nik'];
            $filled = 0;
            foreach ($fields as $f) { if ($this->$f) $filled++; }
            $this->profileCompleteness = round(($filled / count($fields)) * 100);
        }

        // Set Greeting
        $hour = now()->hour;
        if ($hour < 11) $this->greeting = 'Pagi';
        elseif ($hour < 15) $this->greeting = 'Siang';
        elseif ($hour < 18) $this->greeting = 'Sore';
        else $this->greeting = 'Malam';
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|min:3',
            'no_hp' => 'required',
            'bank_name' => 'required',
            'bank_account_number' => 'required',
            'bank_account_name' => 'required',
        ]);

        $user = Auth::user();
        $user->update(['name' => $this->name]);

        $profile = $user->affiliateProfile;
        $profile->update([
            'no_hp' => $this->no_hp,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
        ]);

        session()->flash('profile_success', 'Profil dan rekening berhasil diperbarui.');
    }

    public function changePassword()
    {
        $this->validate([
            'new_password' => 'required|min:8|confirmed',
        ], [
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.min' => 'Password baru minimal 8 karakter.'
        ]);

        Auth::user()->update([
            'password' => \Hash::make($this->new_password)
        ]);

        $this->reset(['new_password', 'new_password_confirmation']);
        session()->flash('password_success', 'Password berhasil diubah.');
    }

    public function getContactAdminWaLink()
    {
        $user = Auth::user();
        $profile = $user->affiliateProfile;
        $adminWa = \App\Models\Setting::getVal('admin_wa', '6281234567890');
        
        $reason = $profile->status === 'inactive' ? 'dinonaktifkan' : 'ditolak';
        $instruction = $profile->status === 'inactive' ? 'apa yang perlu saya lakukan agar akun saya aktif kembali' : 'hal apa yang perlu saya perbaiki';

        $text = "Halo Admin RentSpace,\n\n" .
               "Saya *{$user->name}*, ingin menanyakan terkait status pendaftaran Affiliator saya yang {$reason}. Mohon informasinya terkait {$instruction}.\n\n" .
               "Terima kasih!";
        
        if (strpos($adminWa, '0') === 0) $adminWa = '62' . substr($adminWa, 1);

        return "https://wa.me/{$adminWa}?text=" . urlencode($text);
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('affiliate.login');
    }

    public function render()
    {
        $user = Auth::user();
        $referralCode = $user->affiliateProfile?->referral_code;
        
        $activePromos = \App\Models\PricingRule::where('is_active', true)
            ->where(function($q) use ($referralCode) {
                if ($referralCode) {
                    $q->where('affiliate_code', $referralCode);
                }
                $q->orWhere('requires_referral', true)
                  ->orWhere('is_affiliate_only', true);
            })
            ->where(function($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })->get();

        $profile = Auth::user()->affiliateProfile;
        
        $units = [];
        if ($this->tab === 'promos') {
            $units = \App\Models\Unit::query()
                ->where('is_active', true)
                ->when($this->unitSearch, function($q) {
                    $q->where('name', 'like', '%' . $this->unitSearch . '%');
                })
                ->with('category')
                ->latest()
                ->take(10)
                ->get();
        }

        return view('livewire.affiliate.dashboard', [
            'activePromos' => $activePromos,
            'walletBalance' => $this->walletBalance,
            'payoutHistory' => \App\Models\AffiliatePayout::where('affiliator_id', Auth::id())->latest()->get(),
            'units' => $units,
        ])->layout('layouts.app', ['title' => 'Affiliate Dashboard']);
    }

    public function getRequestVerificationWaLink()
    {
        $user = Auth::user();
        $profile = $user->affiliateProfile;
        if (!$profile) return '#';

        $adminWa = \App\Models\Setting::getVal('admin_wa', '6281234567890');
        $reviewLink = route('admin.affiliate', ['review_id' => $profile->id]);
        
        $text = "Halo Admin RentSpace,\n\n" .
               "Saya *{$user->name}* ingin mengajukan verifikasi pendaftaran sebagai Affiliator.\n\n" .
               "Mohon untuk meninjau data diri saya melalui link berikut:\n" .
               "{$reviewLink}\n\n" .
               "Terima kasih!";
        
        // Ensure 62 format
        if (strpos($adminWa, '0') === 0) $adminWa = '62' . substr($adminWa, 1);

        return "https://wa.me/{$adminWa}?text=" . urlencode($text);
    }
}
