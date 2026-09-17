<?php

namespace App\Filament\Resources\CareerStatisticResource\Pages;

use App\Filament\Resources\CareerStatisticResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerStatistics extends ListRecords
{
    protected static string $resource = CareerStatisticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
