<?php

namespace App\Filament\Resources\CareerWhyJoinCardResource\Pages;

use App\Filament\Resources\CareerWhyJoinCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareerWhyJoinCard extends EditRecord
{
    protected static string $resource = CareerWhyJoinCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
