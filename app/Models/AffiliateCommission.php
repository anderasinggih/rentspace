<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliator_id',
        'rental_id',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    /**
     * The affiliator (user) who earned this commission.
     */
    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliator_id');
    }

    /**
     * The rental that generated this commission.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
