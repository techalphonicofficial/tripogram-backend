<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponApplied extends Model
{
    protected $table = 'coupon_applied';

    protected $fillable = [
        'info_get_id',
        'coupon_id',
        'coupon_amount',
        'status'
    ];

    public function person()
    {
        return $this->belongsTo(InfoGet::class, 'info_get_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}