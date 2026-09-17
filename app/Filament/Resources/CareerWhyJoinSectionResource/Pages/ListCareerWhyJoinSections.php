<?php

namespace App\Filament\Resources\CareerWhyJoinSectionResource\Pages;

use App\Filament\Resources\CareerWhyJoinSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerWhyJoinSections extends ListRecords
{
    protected static string $resource = CareerWhyJoinSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
