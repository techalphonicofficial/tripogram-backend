<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailQueue extends Model
{
    protected $table = 'email_queues';
    
    protected $fillable = [
        'booking_id',
        'status'
    ];
}