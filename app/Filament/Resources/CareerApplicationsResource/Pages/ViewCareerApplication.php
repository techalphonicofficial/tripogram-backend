<?php

namespace App\Filament\Resources\CareerApplicationsResource\Pages;

use App\Filament\Resources\CareerApplicationsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCareerApplication extends ViewRecord
{
    protected static string $resource = CareerApplicationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
