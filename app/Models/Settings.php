<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
     protected $fillable = [
        'privyr_webhook_url',
        'razorpay_key_id',
        'razorpay_key_secret',
        'robots_txt',
        'package_amount_percent',
       'gtm_header',
       'gtm_footer',
      'popular_title',
      'popular_description',
    ];
}
