<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookings extends Model
{
     protected $fillable = [
        'booking_token',
        'booking_id',
        'full_name',
        'email',
        'phone',
        'package_id',
        'seat_ids',
        'package_title',
        'duration',
        'pickup',
        'drop',
        'start_date',
        'end_date',
        'active_cost',
        'data_get',
        'payment_mode',
        'payment_transactions',
        'payment_id',
        'payment_type',
        'final_amount',
        'paid_amount',
        'due_amount',
        'status',
        'payment_history',
    ];

    protected $casts = [
        'active_cost' => 'array',
        'payment_transactions' => 'array',
        'payment_history' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    

    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id', 'id');
    }

    public function seats()
    {
        return $this->hasMany(Seats::class, 'booking_id');
    }
public function feedbacks()
{
    return $this->hasMany(\App\Models\Feedback::class, 'booking_id');
}
    
    public function members()
{
    return $this->hasMany(InfoGet::class, 'booking_id');
}
    
    public function stayLayouts()
{
    return $this->hasMany(StayLayout::class);
}

    protected static function booted()
    {
        static::saving(function ($booking) {
            $booking->calculatePayments();
        });
    }

    public function calculatePayments()
    {
        $paid = collect($this->payment_history)->sum('amount');
        $this->paid_amount = $paid;
        $this->due_amount = max($this->final_amount - $paid, 0);
    }
}
