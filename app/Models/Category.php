<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
