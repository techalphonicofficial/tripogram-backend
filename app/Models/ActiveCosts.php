<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveCosts extends Model
{
    protected $fillable = [
        'package_id',
        'activity',
        'cost',
        'discount_percent',
        'gst_percent',
      'show_on_website'
    ];
    protected $appends = [
        'total_with_discount',
        'total_with_discount_and_gst',
    ];
    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id');
    }
    public function getTotalWithDiscountAttribute()
    {
        $discount = ($this->discount_percent / 100) * $this->cost;
        return round($this->cost - $discount, 2);
    }

    // ✅ Discount + GST Price
    public function getTotalWithDiscountAndGstAttribute()
    {
        $discounted = $this->total_with_discount;
        $gst = ($this->gst_percent / 100) * $discounted;
        return round($discounted + $gst, 2);
    }
}
