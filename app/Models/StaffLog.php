<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Database\Eloquent\Prunable;

class StaffLog extends Model
{
    use Prunable;

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'data_before',
        'data_after',
        'ip_address'
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
