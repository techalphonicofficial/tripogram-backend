<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupSetting extends Model
{
    protected $table = 'popup_settings';

    protected $fillable = [
        'form_heading',
        'submit_button_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
