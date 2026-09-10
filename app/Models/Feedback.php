<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'booking_id',
        'member_id',
        'name',
        'contact',
        'destination',
        'departure_date',
        'travel_rating',
        'stay_rating',
        'meal_rating',
        'captain_rating',
        'itinerary_rating',
        'overall_rating',
        'suggestion',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'overall_rating' => 'decimal:1',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

public function getBookingAttribute()
{
    return Bookings::where('id', $this->booking_id)
        ->orWhere('booking_id', $this->booking_id)
        ->first();
}

    public function infoGet()
    {
        return $this->belongsTo(InfoGet::class, 'member_id');
    }
}