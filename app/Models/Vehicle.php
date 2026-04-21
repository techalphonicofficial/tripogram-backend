<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
   use HasFactory;

    protected $fillable = [
        'vehicle_type',
        'vehicle_seats',
        'vehicle_row',
    ];
}
