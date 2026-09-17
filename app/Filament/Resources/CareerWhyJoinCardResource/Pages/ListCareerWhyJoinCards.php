<?php

namespace App\Filament\Resources\CareerWhyJoinCardResource\Pages;

use App\Filament\Resources\CareerWhyJoinCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerWhyJoinCards extends ListRecords
{
    protected static string $resource = CareerWhyJoinCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
