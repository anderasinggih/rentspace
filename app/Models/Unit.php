<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}
