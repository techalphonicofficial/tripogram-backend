<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $table = 'emails';

    protected $fillable = [
        'email',
        'status',
    ];

    public $timestamps = false; // agar created_at use nahi karna
}