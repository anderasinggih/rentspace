<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $guarded = ['id'];

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}
