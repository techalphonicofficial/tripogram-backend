<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'is_used',
        'expires_at'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ✅ Ensure timestamps are enabled
    public $timestamps = true;

    public function applied()
    {
        return $this->hasOne(CouponApplied::class, 'coupon_id');
    }
}