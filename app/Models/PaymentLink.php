<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    use HasFactory;

    protected $table = 'payment_links';

    protected $fillable = [
        'booking_id',
        'razorpay_link_id',
        'payment_link',
        'amount',
        'status',
        'expire_at',
      'cron_job',
    ];

    protected $casts = [
        'amount' => 'float',
        'expire_at' => 'datetime',
    ];

    // 🔥 Relation with Booking
    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_id', 'booking_id');
    }

    // 🔥 Check if link is active
    public function isActive()
    {
        return $this->status === 'pending' && 
               ($this->expire_at === null || $this->expire_at->isFuture());
    }
}