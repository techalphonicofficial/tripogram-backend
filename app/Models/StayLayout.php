<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StayLayout extends Model
{
    protected $table = 'stay_layouts';

    protected $fillable = [
        'package_id',
        'booking_id',
        'room_id',        // Changed from room_type_id to room_id
        'bed_number',      // Changed from bed_no to bed_number
        'guest_name',
        'guest_email',     // Add these if you want
        'guest_contact',   // Add these if you want
        'guest_gender',    // Add these if you want
        'start_date',
        'end_date',
        'status',
        'person_id',
        'room_type',
        'is_captain'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
   public function person()
{
    return $this->belongsTo(InfoGet::class, 'person_id');
}
    public function package(){
        return $this->belongsTo(Packages::class,'package_id');
    }

    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_id'); // Note: Bookings model (plural)
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id'); // Changed from room_type_id to room_id
    }
}