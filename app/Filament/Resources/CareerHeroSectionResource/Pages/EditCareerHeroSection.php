<?php

namespace App\Filament\Resources\CareerHeroSectionResource\Pages;

use App\Filament\Resources\CareerHeroSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareerHeroSection extends EditRecord
{
    protected static string $resource = CareerHeroSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
