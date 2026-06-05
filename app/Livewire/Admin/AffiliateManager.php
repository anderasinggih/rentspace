<?php

namespace App\Livewire\Admin;

use App\Models\AffiliatorProfile;
use App\Models\AffiliatePayout;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class AffiliateManager extends Component
{
    use WithPagination;

    #[Url]
    public $review_id = null;
    
    public $search = '';
    public $perPage = 10;

    #[Url]
    public $tab = 'request'; // request, account, payouts, history

    public $editingAffiliatorId = null;
    public $commission_rate = 0;

    #[Url]
    public $processingPayoutId = null;
    public $payout_note = '';
    public $payoutActionSuccess = false;

    public $deactivatingProfileId = null;
    public $status_note = '';

    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function mount()
    {
        if (!in_array(auth()->user()->role, ['admin', 'viewer'])) {
            abort(403);
        }

        if ($this->processingPayoutId) {
            $this->tab = 'payouts';
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedTab($value)
    {
        $this->review_id = null;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function closeProcessing()
    {
        $this->processingPayoutId = null;
        $this->payout_note = '';
        $this->payoutActionSuccess = false;
    }

    public function approve($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $profile = AffiliatorProfile::with('user')->findOrFail($id);
        
        // Generate referral code if empty
        if (empty($profile->referral_code)) {
            $profile->referral_code = AffiliatorProfile::generateCode($profile->user->name);
        }

        $profile->status = 'approved';
        $profile->status_note = null; // Clear any deactivation/rejection reasons
        $profile->save();

        session()->flash('last_action_id', $profile->id);
        session()->flash('success', 'Affiliator berhasil disetujui. Kode Referral: ' . $profile->referral_code);
    }

    public function reject($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $profile = AffiliatorProfile::findOrFail($id);
        $profile->update([
            'status' => 'rejected',
            'status_note' => $this->status_note ?: 'Data tidak sesuai persyaratan.'
        ]);
        $this->status_note = '';
        session()->flash('last_action_id', $id);
        session()->flash('success', 'Affiliator berhasil ditolak.');
    }

    public function confirmDeactivation($id)
    {
        $this->deactivatingProfileId = $id;
        $this->status_note = '';
    }

    public function cancelDeactivation()
    {
        $this->deactivatingProfileId = null;
        $this->status_note = '';
    }

    public function deactivate()
    {
        if (auth()->user()->role !== 'admin') return;

        $this->validate([
            'status_note' => 'required|min:3'
        ], [
            'status_note.required' => 'Alasan deaktivasi harus diisi.',
            'status_note.min' => 'Alasan minimal 3 karakter.'
        ]);

        $profile = AffiliatorProfile::findOrFail($this->deactivatingProfileId);
        $profile->update([
            'status' => 'inactive',
            'status_note' => $this->status_note
        ]);

        $this->cancelDeactivation();
        session()->flash('success', 'Akun affiliator berhasil dinonaktifkan.');
    }

    public function editCommission($id)
    {
        $profile = AffiliatorProfile::findOrFail($id);
        $this->editingAffiliatorId = $id;
        $this->commission_rate = $profile->commission_rate;
    }

    public function saveCommission()
    {
        if (auth()->user()->role !== 'admin') return;

        $profile = AffiliatorProfile::findOrFail($this->editingAffiliatorId);
        $profile->update(['commission_rate' => $this->commission_rate]);
        $this->editingAffiliatorId = null;
        session()->flash('success', 'Komisi berhasil diperbarui.');
    }

    public function syncPartnerBalance($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $profile = AffiliatorProfile::findOrFail($id);
        $user = $profile->user;

        // 1. Ensure all qualified rentals have commission records
        $rentals = \App\Models\Rental::where('affiliator_id', $user->id)
            ->whereIn('status', ['paid', 'completed'])
            ->get();

        foreach ($rentals as $rental) {
            $exists = \App\Models\AffiliateCommission::where('rental_id', $rental->id)->exists();
            if (!$exists) {
                $amount = $rental->subtotal_harga * ($profile->commission_rate / 100);
                \App\Models\AffiliateCommission::create([
                    'affiliator_id' => $user->id,
                    'rental_id' => $rental->id,
                    'amount' => $amount,
                    'status' => 'earned'
                ]);
            }
        }

        // 2. Perform absolute recalculation using model method
        $newBalance = $profile->syncBalance();

        session()->flash('success', "Sinkronisasi saldo [{$profile->user->name}] berhasil. Saldo saat ini: Rp " . number_format($newBalance, 0, ',', '.'));
    }

    public function processPayout($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $payout = AffiliatePayout::findOrFail($id);
        $payout->update([
            'status' => 'processed',
            'note' => $this->payout_note
        ]);
        
        // Mark related commissions as paid
        $payout->affiliator->commissions()->where('status', 'payout_pending')->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $this->syncPartnerBalance($payout->affiliator->affiliateProfile->id);

        $this->payoutActionSuccess = true;
        session()->flash('success', 'Payout berhasil diproses.');
    }

    public function rejectPayout($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $payout = AffiliatePayout::findOrFail($id);
        $payout->update([
            'status' => 'rejected',
            'note' => $this->payout_note
        ]);

        // Revert commissions back to earned (they were likely payout_pending)
        $payout->affiliator->commissions()->where('status', 'payout_pending')->update([
            'status' => 'earned'
        ]);

        $this->syncPartnerBalance($payout->affiliator->affiliateProfile->id);

        $this->payoutActionSuccess = true;
        session()->flash('success', 'Payout ditolak dan saldo telah dikembalikan.');
    }

    public function deletePayout($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $payout = AffiliatePayout::findOrFail($id);
        $affiliatorId = $payout->affiliator_id;
        $profileId = $payout->affiliator->affiliateProfile->id ?? null;

        $payout->delete();

        if ($profileId) {
            $this->syncPartnerBalance($profileId);
        }

        session()->flash('success', 'Data payout berhasil dihapus.');
    }

    public function getWaNotificationLink($payout)
    {
        $profile = $payout->affiliator->affiliateProfile;
        $no_wa = $profile?->no_hp ?? '';
        if (empty($no_wa)) return '#';

        // Format to 62...
        if (strpos($no_wa, '0') === 0) {
            $no_wa = '62' . substr($no_wa, 1);
        }

        $statusText = $payout->status === 'processed' ? 'BERHASIL/SUKSES' : 'DITOLAK';
        $adminFee = $payout->admin_fee ?? 0;
        $netAmount = $payout->amount - $adminFee;

        $message = "Halo {$payout->affiliator->name},\n\n" .
                   "Kami ingin mengonfirmasi status penarikan komisi Anda:\n\n" .
                   "ID Payout: #{$payout->id}\n" .
                   "Nominal Tarik: Rp " . number_format($payout->amount, 0, ',', '.') . "\n" .
                   "Biaya Admin: Rp " . number_format($adminFee, 0, ',', '.') . "\n" .
                   "Total Ditransfer: Rp " . number_format($netAmount, 0, ',', '.') . "\n" .
                   "Status: *{$statusText}*\n";
        
        if ($payout->note) {
            $message .= "Keterangan: {$payout->note}\n";
        }

        if ($payout->status === 'processed') {
            $message .= "\nDana telah ditransfer ke rekening Anda. Mohon diperiksa. Terima kasih!";
        } else {
            $message .= "\nMohon maaf atas ketidaknyamanannya. Silakan periksa kembali data Anda atau hubungi admin.";
        }

        return "https://wa.me/{$no_wa}?text=" . urlencode($message);
    }

    public function getAffiliateStatusWaLink($id)
    {
        $profile = AffiliatorProfile::with('user')->findOrFail($id);
        $no_wa = $profile->no_hp ?? '';
        if (empty($no_wa)) return '#';

        // Format to 62...
        if (strpos($no_wa, '0') === 0) {
            $no_wa = '62' . substr($no_wa, 1);
        }

        $statusText = $profile->status === 'approved' ? '*DISETUJUI*' : '*DITOLAK*';
        
        $message = "Halo {$profile->user->name},\n\n" .
                   "Kami ingin mengonfirmasi status pendaftaran Affiliator Anda:\n\n" .
                   "Status: {$statusText}\n\n";
        
        if ($profile->status === 'approved') {
            $message .= "Selamat! Akun Anda telah aktif. Silakan login ke dashboard untuk mulai membagikan kode referral Anda: " . route('affiliate.login') . "\n\nTerima kasih!";
        } else {
            $message .= "Mohon maaf, pendaftaran Anda belum dapat kami setujui saat ini. Silakan periksa kembali kelengkapan data Anda atau hubungi kami untuk informasi lebih lanjut.\n\nTerima kasih!";
        }

        return "https://wa.me/{$no_wa}?text=" . urlencode($message);
    }

    public function render()
    {
        $profiles = AffiliatorProfile::with('user')
            ->when($this->tab === 'request', function($q) {
                // Show all onboarding history
            })
            ->when($this->tab === 'account', function($q) {
                $q->where('status', 'approved')
                  ->withSum('commissions as total_earned', 'amount')
                  ->withSum(['payouts as total_withdrawn' => function($query) {
                      $query->whereIn('status', ['pending', 'processed', 'completed']);
                  }], 'amount');
            })
            ->latest()
            ->paginate($this->perPage, ['*'], 'profilesPage');

        $payoutsQuery = AffiliatePayout::with('affiliator', 'affiliator.affiliateProfile')
            ->when($this->tab === 'payouts', function($q) {
                $q->where(function($sub) {
                    $sub->where('status', 'pending')
                        ->orWhere('id', $this->processingPayoutId);
                });
            })
            ->when($this->tab === 'history', fn($q) => $q->whereIn('status', ['processed', 'rejected']));

        // Apply sorting based on field
        if ($this->sortField === 'name') {
            $payoutsQuery->join('users', 'affiliate_payouts.affiliator_id', '=', 'users.id')
                ->orderBy('users.name', $this->sortDirection)
                ->select('affiliate_payouts.*');
        } else {
            $payoutsQuery->orderBy($this->sortField, $this->sortDirection);
        }

        $payouts = $payoutsQuery->paginate($this->perPage, ['*'], 'payoutsPage');

        return view('livewire.admin.affiliate-manager', [
            'profiles' => $profiles,
            'payouts' => $payouts
        ])->layout('layouts.admin');
    }
}
