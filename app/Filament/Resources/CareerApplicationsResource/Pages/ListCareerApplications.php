<?php

namespace App\Filament\Resources\CareerApplicationsResource\Pages;

use App\Filament\Resources\CareerApplicationsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerApplications extends ListRecords
{
    protected static string $resource = CareerApplicationsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
