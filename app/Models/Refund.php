<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'booking_id',
        'amount',
        'reason',
      'type',
        'image',
    ];

    public function booking()
    {
        return $this->belongsTo(Bookings::class);
    }
}