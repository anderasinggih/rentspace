<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PinnedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'group_jid',
        'sender_phone',
        'sender_name',
        'message_text',
        'is_pinned',
        'pinned_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];
}

