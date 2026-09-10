<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $table = 'batches';
    
    protected $fillable = [
        'batch_name',
        'status'
    ];
    
    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Relationships
    public function bookings(): HasMany
    {
        return $this->hasMany(Bookings::class, 'batch_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
    
    // Accessors
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-danger">Inactive</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }
}