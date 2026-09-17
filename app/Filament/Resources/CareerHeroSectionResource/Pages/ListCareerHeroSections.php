<?php

namespace App\Filament\Resources\CareerHeroSectionResource\Pages;

use App\Filament\Resources\CareerHeroSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerHeroSections extends ListRecords
{
    protected static string $resource = CareerHeroSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
