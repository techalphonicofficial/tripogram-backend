<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packages extends Model
{

    protected $fillable = [
        'trip_id',
      'sort_order',
        'destination_id',
        'vehicle_id',
        'banner',
      'map_image',
        'thumbnail',
        'title',
        'slug',
        'duration',
        'starting_price',
        'pickup',
        'drop',
        'age_group_min',
        'age_group_max',
        'description',
        'itinerary',
        'itinerary_pdf',
        'inclusion',
        'exclusion',
        'note',
        'things_to_pack',
        'gallery',
        'testimonials',
        'faqs',
        'related_insta_video',
        'related_youtube_video',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
        'is_trending',
       'slot',
      'is_land_package',
    'booking_amount',
    ];
    protected $casts = [
        'itinerary' => 'array',
        'gallery' => 'array',
        'testimonials' => 'array',
        'faqs' => 'array',
        'related_insta_video' => 'array',
        'related_youtube_video' => 'array',
    ];
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    public function activeCosts()
    {
        return $this->hasMany(ActiveCosts::class, 'package_id');
    }
public function bookings()
{
    return $this->hasMany(Bookings::class, 'package_id', 'id');
}
    public function packageDates()
    {
        return $this->hasMany(PackageDates::class, 'package_id');
    }
    public function addonSchema()
    {
        return $this->hasMany(PackageAddonSchemas::class, 'package_id');
    }
  
  public function trips()
{
    return $this->belongsToMany(Trips::class, 'package_map_with_trips', 'package_id', 'trip_id')
                ->withPivot('mapping_date', 'status')
                ->withTimestamps(); // or use $timestamps = false in the pivot model
}
  
  
     public function trippu(){
   	return $this->hasMany(PackageMapWithTrip::class,'package_id') ;
  }
    protected static function booted()
    {
        static::deleting(function ($package) {
            $package->activeCosts()->delete();
            $package->packageDates()->delete();
        });
    }
    public function getThumbnailAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
    public function getBannerAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
    public function getItineraryPdfAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
  
    
    public function destination()
    {
        return $this->belongsTo(Destinations::class, 'destination_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trips::class, 'trip_id');
    }
    public function packageVehicles()
    {
        return $this->hasMany(PackageVehicle::class, 'package_id', 'id');
    }
}
