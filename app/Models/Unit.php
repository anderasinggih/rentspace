<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'specs' => 'array',
    ];

    protected $guarded = ['id'];

    public function rentals()
    {
        return $this->belongsToMany(Rental::class, 'rental_items')->withPivot('price_snapshot')->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(RentalItem::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function locations()
    {
        return $this->hasMany(UnitLocation::class);
    }
}
