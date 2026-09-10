<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageQueue extends Model
{
    protected $table = 'message_queues';

    protected $fillable = [
        'type',
        'to_value',
        'payload',
        'status',
        'attempts',
        'error'
    ];

    protected $casts = [
        'payload' => 'array'
    ];
}