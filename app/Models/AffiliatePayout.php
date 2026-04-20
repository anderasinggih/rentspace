<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayout extends Model
{
    protected $fillable = [
        'affiliator_id',
        'amount',
        'admin_fee',
        'status',
        'proof_of_payment',
        'note',
    ];

    /**
     * The affiliator (user) who requested this payout.
     */
    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliator_id');
    }
}
