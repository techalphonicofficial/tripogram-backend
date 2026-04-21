<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainPages extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];
    public function sections()
    {
        return $this->hasMany(PageSections::class, 'page_id');
    }
    public function addonSchemas()
    {
        return $this->hasMany(PageAddonSchemas::class, 'page_id');
    }
}
