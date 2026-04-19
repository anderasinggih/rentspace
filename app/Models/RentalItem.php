<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    protected $guarded = ['id'];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
