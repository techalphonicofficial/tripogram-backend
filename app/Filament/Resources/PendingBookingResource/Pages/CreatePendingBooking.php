<?php

namespace App\Filament\Resources\PendingBookingResource\Pages;

use App\Filament\Resources\PendingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePendingBooking extends CreateRecord
{
    protected static string $resource = PendingBookingResource::class;
}
