<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerHeroLabel extends Model
{
    protected $table = 'career_hero_labels';

    protected $fillable = [
        'career_hero_section_id',
        'text',
        'position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function heroSection()
    {
        return $this->belongsTo(CareerHeroSection::class, 'career_hero_section_id');
    }
}
