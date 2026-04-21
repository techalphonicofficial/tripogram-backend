<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirections extends Model
{
    protected $fillable = [
        'from_url',
        'to_url',
        'to_type',
        'status',
    ];
}
