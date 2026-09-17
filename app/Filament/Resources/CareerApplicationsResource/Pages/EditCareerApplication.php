<?php

namespace App\Filament\Resources\CareerApplicationsResource\Pages;

use App\Filament\Resources\CareerApplicationsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareerApplication extends EditRecord
{
    protected static string $resource = CareerApplicationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
