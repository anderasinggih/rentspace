<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $fillable = [
        'rental_id',
        'recipient',
        'subject',
        'type',
        'status',
        'error',
        'sent_at'
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}
