<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destinations extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'thumbnail',
        'banner',
        'meta_title',
      'content',
      'state_code',
        'meta_description',
        'meta_keywords',
        'is_active',
        'show_in_home',
       'meta_schema',
    'faq'
    ];
    protected $casts = [
    'faq' => 'array',
];
    public function packages()
    {
        return $this->hasMany(Packages::class, 'destination_id');
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
}
