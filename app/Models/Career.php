<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Career extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'department',
        'location',
        'job_type',
        'experience',
        'salary',
        'short_description',
        'description',
        'requirements',
        'skills',
        'application_deadline',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'skills'               => 'array',
        'is_active'            => 'boolean',
        'application_deadline' => 'date',
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function applications()
    {
        return $this->hasMany(CareerApplication::class, 'career_id');
    }
}
