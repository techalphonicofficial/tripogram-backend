<?php

namespace App\Filament\Resources\CareerSettingsResource\Pages;

use App\Filament\Resources\CareerSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerSettings extends ListRecords
{
    protected static string $resource = CareerSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ Add Setting'),
        ];
    }
}
