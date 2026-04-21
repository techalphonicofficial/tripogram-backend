<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletters extends Model
{
    protected $fillable = [
        'email',
        'status',
    ];
}
