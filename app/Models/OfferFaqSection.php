<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferFaqSection extends Model
{
    protected $table = 'offer_faq_sections';

    protected $fillable = [
        'heading',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
