<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferTravelSpotsSection extends Model
{
    protected $table = 'offer_travel_spots_sections';

    protected $fillable = [
        'small_label',
        'heading',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
