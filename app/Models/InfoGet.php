<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoGet extends Model
{
    use HasFactory;

    protected $table = 'info_get';

    protected $fillable = [
        'package_id',
        'booking_id',
        'start_date',

        'sharing_type',
        'member_number',

        'name',
        'email',
        'contact',
        'gender',
        'dob',

        'id_proof_type',
        'id_proof_number',

        'emergency_name',
        'emergency_contact',
        'emergency_relation',

        'has_file'
    ];
    
    public function booking()
{
    return $this->belongsTo(Bookings::class, 'booking_id');
}
public function package(){
    return $this->belongsTo(package::class,'package_id');
}
}