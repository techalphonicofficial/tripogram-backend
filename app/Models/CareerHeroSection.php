<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerHeroSection extends Model
{
    protected $table = 'career_hero_sections';

    protected $fillable = [
        'label',
        'heading_line_1',
        'heading_line_2',
        'heading_line_3',
        'heading_highlight_color',
        'description',
        'sub_text',
        'button_text',
        'button_url',
        'main_image',
        'secondary_image',
        'background_image',
        'team_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function labels()
    {
        return $this->hasMany(CareerHeroLabel::class, 'career_hero_section_id');
    }

    public function getMainImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }

    public function getSecondaryImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }

    public function getBackgroundImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}
