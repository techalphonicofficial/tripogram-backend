<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerWhyJoinSection extends Model
{
    protected $table = 'career_why_join_sections';

    protected $fillable = [
        'small_label',
        'heading',
        'highlight_text',
        'description',
        'background_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cards()
    {
        return $this->hasMany(CareerWhyJoinCard::class, 'career_why_join_section_id');
    }

    public function getBackgroundImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}
