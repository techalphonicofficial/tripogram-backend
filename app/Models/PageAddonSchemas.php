<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageAddonSchemas extends Model
{
    protected $fillable = ['page_id', 'schema_type', 'schema'];

    public function page()
    {
        return $this->belongsTo(MainPages::class, 'page_id');
    }
}
