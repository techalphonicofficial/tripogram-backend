<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LatestBlogDetail extends Model
{
    // Specify the table name
    protected $table = 'latest_blog_details';

    // Fillable fields for mass assignment
    protected $fillable = [
        'post_id',
        'image',
        'alt',
        'content',
      'created_at',
        'updated_at',
    ];

    /**
     * Relationship to the parent blog
     * Each detail belongs to one blog
     */
      public $timestamps = true;
    public function blog()
    {
        return $this->belongsTo(Blogs::class, 'post_id', 'id');
    }

    /**
     * Accessor for image URL when using API
     */
    public function getImageAttribute($value)
    {
        if (request()->is('api/*')) {
            return $value ? url('storage/' . $value) : null;
        }
        return $value;
    }
}