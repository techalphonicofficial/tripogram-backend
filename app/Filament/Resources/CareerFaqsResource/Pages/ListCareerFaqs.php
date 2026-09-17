<?php

namespace App\Filament\Resources\CareerFaqsResource\Pages;

use App\Filament\Resources\CareerFaqsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerFaqs extends ListRecords
{
    protected static string $resource = CareerFaqsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
