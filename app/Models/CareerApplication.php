<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CareerApplication extends Model
{
    protected $fillable = [
        'career_id',
        'name',
        'email',
        'phone',
        'cover_letter',
        'resume',
        'linkedin_url',
        'portfolio_url',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function career()
    {
        return $this->belongsTo(Career::class, 'career_id');
    }

    // =============================================
    // ACCESSORS
    // =============================================

    /**
     * Get signed (temporary) resume download URL — for admin use only.
     * Never expose this on public API.
     */
    public function getResumeDownloadUrlAttribute(): ?string
    {
        if (!$this->resume) {
            return null;
        }
        // Resume stored on local (private) disk
        return Storage::disk('local')->exists($this->resume)
            ? url('/admin/career-applications/' . $this->id . '/download-resume')
            : null;
    }
}
