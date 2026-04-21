<?php

namespace App\Filament\Resources\ContactEnquiriesResource\Pages;

use App\Filament\Resources\ContactEnquiriesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactEnquiries extends EditRecord
{
    protected static string $resource = ContactEnquiriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
