<?php

namespace App\Filament\Resources\PendingBookingResource\Pages;

use App\Filament\Resources\PendingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendingBooking extends EditRecord
{
    protected static string $resource = PendingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
