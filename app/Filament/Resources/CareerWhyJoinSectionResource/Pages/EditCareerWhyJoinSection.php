<?php

namespace App\Filament\Resources\CareerWhyJoinSectionResource\Pages;

use App\Filament\Resources\CareerWhyJoinSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareerWhyJoinSection extends EditRecord
{
    protected static string $resource = CareerWhyJoinSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
