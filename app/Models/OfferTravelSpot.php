<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferTravelSpot extends Model
{
    protected $table = 'offer_travel_spots';

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}
