<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferCard extends Model
{
    protected $table = 'offer_cards';

    protected $fillable = [
        'title',
        'description',
        'icon',
        'icon_image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getIconImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}
