<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';

    protected $fillable = [
        'room_type',
        'no_of_beds',
        'status',
        
    ];

   public function stayLayouts()
{
    return $this->hasMany(StayLayout::class, 'room_type_id');
}
}
