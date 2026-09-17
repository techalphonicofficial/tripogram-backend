<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerStatistic extends Model
{
    protected $table = 'career_statistics';

    protected $fillable = [
        'icon',
        'number',
        'title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getIconAttribute($value)
    {
        if (request()->is('api/*') && $value && Storage::disk('public')->exists($value)) {
            return url('storage/' . $value);
        }
        return $value;
    }
}
