<?php

namespace App\Filament\Resources\CareerBenefitsResource\Pages;

use App\Filament\Resources\CareerBenefitsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerBenefits extends ListRecords
{
    protected static string $resource = CareerBenefitsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
