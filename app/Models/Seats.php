<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seats extends Model
{
    protected $fillable = [
        'package_vehicle_id', 
        'seat_no', 
        'booking_id',
        'gender',
        'passenger_name',  // Added
        'passenger_contact', // Added
        'sharing_type' ,
        'is_captain'
            // Added
    ];

    public function packageVehicle()
    {
        return $this->belongsTo(PackageVehicle::class, 'package_vehicle_id', 'id');
    }

    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_id');
    }
}