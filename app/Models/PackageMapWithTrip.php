<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageMapWithTrip extends Model
{
    use HasFactory;

    protected $table = 'package_map_with_trips';

    protected $fillable = [
        'package_id',
        'trip_id',
        'mapping_date',
        'status',
    ];
public function packagse()
{
    return $this->belongsTo(\App\Models\Packages::class, 'package_id');
}

    public $timestamps = false; // since you're using custom timestamp column
}