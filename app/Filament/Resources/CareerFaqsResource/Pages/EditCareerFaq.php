<?php

namespace App\Filament\Resources\CareerFaqsResource\Pages;

use App\Filament\Resources\CareerFaqsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareerFaq extends EditRecord
{
    protected static string $resource = CareerFaqsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
