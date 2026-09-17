<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerWhyJoinCard extends Model
{
    protected $table = 'career_why_join_cards';

    protected $fillable = [
        'career_why_join_section_id',
        'icon',
        'title',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function whyJoinSection()
    {
        return $this->belongsTo(CareerWhyJoinSection::class, 'career_why_join_section_id');
    }

    public function getIconAttribute($value)
    {
        if (request()->is('api/*') && $value && Storage::disk('public')->exists($value)) {
            return url('storage/' . $value);
        }
        return $value;
    }
}
