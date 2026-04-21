<?php

namespace App\Livewire\Affiliate;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\AffiliatePayout;
use App\Models\Setting;

class PayoutRequest extends Component
{
    public $payoutStep = 'amount'; // amount, details, final
    public $payoutAmount;
    public $requestedPayoutId;
    public const ADMIN_FEE = 2500;

    public function getWalletBalanceProperty()
    {
        $user = Auth::user();
        if (!$user) return 0;
        
        $profile = $user->affiliateProfile;
        $dbBalance = $profile->balance ?? 0;

        // Fallback calculation for accuracy
        $totalWithdrawn = $user->payouts()->whereIn('status', ['pending', 'processed', 'completed'])->sum('amount');
        $sumBalance = $user->commissions()->sum('amount') - $totalWithdrawn;
        
        return max($dbBalance, $sumBalance);
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'affiliator') {
            return redirect()->route('admin.dashboard');
        }

        $profile = $user->affiliateProfile;
        if (!$profile || $profile->status !== 'approved') {
            session()->flash('error', 'Akun Anda belum disetujui untuk melakukan penarikan.');
            return redirect()->route('affiliate.dashboard');
        }
    }

    public function nextToDetails()
    {
        $minPayout = (int)Setting::getVal('min_payout', 50000);
        $maxPayout = (float)$this->walletBalance;

        $this->validate([
            'payoutAmount' => "required|numeric|min:{$minPayout}|max:{$maxPayout}",
        ], [
            'payoutAmount.min' => 'Minimal penarikan adalah Rp ' . number_format($minPayout, 0, ',', '.'),
            'payoutAmount.max' => 'Saldo Anda tidak mencukupi.',
            'payoutAmount.required' => 'Masukkan nominal penarikan.',
        ]);

        $this->payoutStep = 'details';
    }

    public function submitPayoutRequest()
    {
        $user = Auth::user();
        $balance = $this->walletBalance;

        if ($balance < $this->payoutAmount) {
            $this->addError('payoutAmount', 'Saldo tidak mencukupi.');
            return;
        }

        $payout = AffiliatePayout::create([
            'affiliator_id' => $user->id,
            'amount' => $this->payoutAmount,
            'admin_fee' => self::ADMIN_FEE,
            'status' => 'pending',
        ]);

        $user->affiliateProfile->syncBalance();

        $this->requestedPayoutId = $payout->id;
        $this->payoutStep = 'final';
    }

    public function render()
    {
        $user = Auth::user();
        $profile = $user->affiliateProfile;
        
        $waLink = '';
        if ($this->payoutStep === 'final' && $this->requestedPayoutId) {
            $adminWa = Setting::getVal('admin_wa', '6281234567890');
            $netAmount = $this->payoutAmount - self::ADMIN_FEE;
            
            $reviewLink = route('admin.affiliate', ['tab' => 'payouts', 'processingPayoutId' => $this->requestedPayoutId]);

            $text = "Halo Admin RentSpace,\n\n" .
                   "Saya ingin mengonfirmasi permintaan penarikan komisi affiliate:\n\n" .
                   "ID Payout: #{$this->requestedPayoutId}\n" .
                   "Referral Code: " . ($profile->referral_code ?? '-') . "\n" .
                   "Nominal Tarik: Rp " . number_format($this->payoutAmount, 0, ',', '.') . "\n" .
                   "Biaya Admin: Rp " . number_format(self::ADMIN_FEE, 0, ',', '.') . "\n" .
                   "Total Diterima: Rp " . number_format($netAmount, 0, ',', '.') . "\n\n" .
                   "Detail Bank:\n" .
                   "Bank: {$profile->bank_name}\n" .
                   "Rekening: {$profile->bank_account_number}\n" .
                   "Atas Nama: {$profile->bank_account_name}\n\n" .
                   "Link Review (Admin Only):\n{$reviewLink}\n\n" .
                   "Mohon segera diproses, terima kasih!";
            
            $waLink = "https://wa.me/{$adminWa}?text=" . urlencode($text);
        }

        return view('livewire.affiliate.payout-request', [
            'profile' => $profile,
            'waLink' => $waLink
        ])->layout('layouts.app', ['title' => 'Ajukan Payout']);
    }
}
