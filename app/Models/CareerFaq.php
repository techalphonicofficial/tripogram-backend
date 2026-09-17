<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerFaq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];
}
