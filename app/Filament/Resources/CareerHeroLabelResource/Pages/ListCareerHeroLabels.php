<?php

namespace App\Filament\Resources\CareerHeroLabelResource\Pages;

use App\Filament\Resources\CareerHeroLabelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerHeroLabels extends ListRecords
{
    protected static string $resource = CareerHeroLabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
