<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageVehicle extends Model
{
    protected $fillable = ['package_id','package_date_id', 'vehicle_id', 'label','captain_name'];

    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id', 'id');
    }
    public function date()
    {
        return $this->belongsTo(PackageDates::class, 'package_date_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function seats()
    {
        return $this->hasMany(Seats::class, 'package_vehicle_id', 'id');
    }
}
