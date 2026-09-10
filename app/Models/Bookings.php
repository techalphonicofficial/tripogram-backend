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
       'batch_id',
        'package_id',
       'gst_no',
        'seat_ids',
        'package_title',
        'duration',
       'source',
        'pickup',
        'drop',
        'start_date',
        'end_date',
        'active_cost',
        'data_get',
        'payment_mode',
        'payment_transactions',
        'payment_id',
       'assign_to',
        'payment_type',
        'final_amount',
        'paid_amount',
        'due_amount',
        'status',
       'booking_type',
        'payment_history',
         'applied_coupons',
    'total_coupon_discount',
       'cron_job',
       'special_note',
    ];

    protected $casts = [
   
    'applied_coupons' => 'array',
   'total_coupon_discount' => 'float',
        'active_cost' => 'array',
        'payment_transactions' => 'array',
        'payment_history' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
      public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
public function paymentLinks()
{
    return $this->hasMany(PaymentLink::class, 'booking_id', 'id');
}
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


public function assignedUser()
{
    return $this->belongsTo(User::class, 'assign_to', 'id');
}
    public function calculatePayments()
    {
        $paid = collect($this->payment_history)->sum('amount');
        $this->paid_amount = $paid;
        $this->due_amount = max($this->final_amount - $paid, 0);
    }
}
