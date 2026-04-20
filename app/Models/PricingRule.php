<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingRule extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];

    protected $casts = [
        'is_affiliate_only' => 'boolean',
        'requires_referral' => 'boolean',
        'is_active' => 'boolean',
        'is_hidden' => 'boolean',
        'can_stack' => 'boolean',
    ];
}
