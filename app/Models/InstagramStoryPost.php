<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramStoryPost extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'insights' => 'array',
        'published_at' => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->media_id !== null;
    }
}
