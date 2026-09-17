<?php

namespace App\Filament\Resources\CareerSettingsResource\Pages;

use App\Filament\Resources\CareerSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareerSetting extends EditRecord
{
    protected static string $resource = CareerSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
