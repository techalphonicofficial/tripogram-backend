<?php

namespace App\Events;

use App\Models\PopupForms;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PopupFormCreated
{
    use Dispatchable, SerializesModels;

    public $popupForm;

    public function __construct(PopupForms $popupForm)
    {
        $this->popupForm = $popupForm;
    }
}
