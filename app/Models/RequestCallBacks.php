<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestCallBacks extends Model
{
    protected $fillable = [
        'package_id',
        'full_name',
        'email',
        'phone',
    ];

    // relation with Package
    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id');
    }
}
