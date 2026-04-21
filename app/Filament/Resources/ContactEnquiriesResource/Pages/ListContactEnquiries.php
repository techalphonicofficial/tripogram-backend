<?php

namespace App\Filament\Resources\ContactEnquiriesResource\Pages;

use App\Filament\Resources\ContactEnquiriesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContactEnquiries extends ListRecords
{
    protected static string $resource = ContactEnquiriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
