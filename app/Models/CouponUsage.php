<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;

    protected $table = 'coupon_usages';

    protected $fillable = [
        'coupon_id',
        'booking_id',
        'discount_amount'
    ];

    /**
     * Coupon relation
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Booking relation
     */
    public function booking()
    {
        return $this->belongsTo(Bookings::class);
    }
}