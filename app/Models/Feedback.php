<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'rating',
        'booking_id',
    ];

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'booking_id');
    }
}