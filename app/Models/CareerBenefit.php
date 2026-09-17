<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerBenefit extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'icon',
        'image',
        'short_description',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // =============================================
    // ACCESSORS — API image URLs
    // =============================================

    public function getImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }

    public function getIconAttribute($value)
    {
        if (request()->is('api/*')) {
            // Icon can be a string (icon class name) or uploaded image path
            if ($value && Storage::disk('public')->exists($value)) {
                return url('storage/' . $value);
            }
        }
        return $value;
    }
}
