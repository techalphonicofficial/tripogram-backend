<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupForms extends Model
{
    protected $fillable = [
        'fname',
        'lname',
        'contact',
        'email',
        'message',
    ];
}
