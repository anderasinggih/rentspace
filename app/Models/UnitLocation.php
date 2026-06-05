<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitLocation extends Model
{
    protected $fillable = [
        'unit_id',
        'lat',
        'lng',
        'address',
        'battery_level'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
