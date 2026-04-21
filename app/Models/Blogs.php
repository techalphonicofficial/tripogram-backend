<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blogs extends Model
{
    protected $fillable = [
        'package_id',
        'heading',
        'excerpt',
        'image',
        'banner_alt',
        'content',
        'tags',
        'status',
        'date',
        'meta_title',
        'schema',
        'meta_keywords',
        'meta_description',
        'author_id',
        'slug',
      'faq',
    ];

    // Relationship with blog details
     protected $casts = [
        'faq' => 'array',  // 👈 Automatically cast JSON to array
    ];
    public function details()
    {
        return $this->hasMany(LatestBlogDetail::class, 'post_id', 'id');
    }
public function blogDetails()
{
    return $this->hasMany(LatestBlogDetail::class, 'post_id'); // Adjust foreign key if needed
}
    // Image accessor for API
    public function getImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }

    // Created at accessor
    public function getCreatedAtAttribute($value)
    {
        if (request()->is('api/*')) {
            $value = date("d, M Y", strtotime($value));
        }
        return $value;
    }
}