<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trips extends Model
{
    protected $fillable = [
        "heading",
        "slug",
        "thumbnail",
        "banner",
        "content",
        "international",
        "show_in_home",
        "menu_order",
        "meta_title",
        "meta_description",
        "meta_keywords"
    ];
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
      public function packagess()
    {
        return $this->belongsToMany(Packages::class, 'package_map_with_trips', 'trip_id', 'package_id')
                    ->withPivot('mapping_date', 'status')
                    ->withTimestamps();
    }
  
  public function tripswithpac(){
   	return $this->hasMany(PackageMapWithTrip::class,'trip_id') ;
  }
    public function packages()
    {
        return $this->hasMany(Packages::class, 'trip_id', 'id');
    }
}
