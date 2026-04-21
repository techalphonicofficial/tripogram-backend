<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PageSections extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'section_key',
        'section'
    ];
    protected $casts = [
        'section' => 'array',
    ];
    public function page()
    {
        return $this->belongsTo(MainPages::class, 'page_id');
    }
}
