<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageDates extends Model
{
    protected $fillable = [
        'package_id',
        'event_period',
      'departure_date',
        'start_date',
        'end_date',
        'day_name',
        'slots',
        'slot2',
        'end_day',
        'status',
        'starting_price',
        'increase_amount_by_percent',
        'decrease_amount_by_percent',
        'special',
      'show_on_website'
    ];

    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id');
    }

    protected static function booted()
    {
        
        static::creating(function ($packageDate) {

        // dd($packageDate);
            if ($packageDate->package) {
                $minCost = $packageDate->package->activeCosts()
                    ->get()
                    ->map(function ($row) {
                        $cost = (float) $row->cost;
                        $discount = (float) $row->discount_percent;
                        return $cost - ($cost * $discount / 100);
                    })
                    ->min();

                $packageDate->starting_price = $minCost ?? 0;
            }
        });

        static::saving(function ($model) {
            
            if (isset($model->event_period['start'])) {
                $model->start_date = $model->event_period['start'];
                $model->end_date   = $model->event_period['end'];
            }
            unset($model->event_period);
        });
    }
    public function getStartingPriceAttribute($value)
    {
        $price = (float) $value;

        if ($this->increase_amount_by_percent > 0) {
            $price += $price * $this->increase_amount_by_percent / 100;
        } elseif ($this->decrease_amount_by_percent > 0) {
            $price -= $price * $this->decrease_amount_by_percent / 100;
        }
        return round($price, 2);
    }
}
