<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactEnquiries extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'age',
        'destination',
        'travel_date',
        'message',
    ];
}
