<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppQueue extends Model
{
    protected $table = 'whatsapp_queues';
    
    protected $fillable = [
        'booking_id',
        'status'
    ];
}