<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'referral_code',
        'nik',
        'no_hp',
        'alamat',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'commission_rate',
        'balance',
        'status',
        'status_note',
    ];

    /**
     * Generate a referral code based on the user's name:
     * 2 Initials of first name + 3 Random Digits (e.g., AN123)
     */
    public static function generateCode($name): string
    {
        $initials = strtoupper(substr($name, 0, 2));
        
        // Ensure we have 2 characters
        if (strlen($initials) < 2) {
            $initials = str_pad($initials, 2, 'X');
        }

        do {
            $code = $initials . rand(100, 999);
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * The user associated with this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get commissions for this affiliator.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'affiliator_id', 'user_id');
    }

    /**
     * Get payouts for this affiliator.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class, 'affiliator_id', 'user_id');
    }
    /**
     * Centralized Balance Sync
     * Commissions (earned) - Payouts (pending/processed/completed)
     */
    public function syncBalance(): float
    {
        $totalEarned = \App\Models\AffiliateCommission::where('affiliator_id', $this->user_id)->sum('amount');
        
        $totalWithdrawn = \App\Models\AffiliatePayout::where('affiliator_id', $this->user_id)
            ->whereIn('status', ['pending', 'processed', 'completed'])
            ->sum('amount');

        $this->balance = $totalEarned - $totalWithdrawn;
        $this->save();

        return (float) $this->balance;
    }
}
