<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferHeroSection extends Model
{
    protected $table = 'offer_hero_sections';

    protected $fillable = [
        'navbar_text',
        'small_label',
        'heading',
        'description',
        'background_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getBackgroundImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}
