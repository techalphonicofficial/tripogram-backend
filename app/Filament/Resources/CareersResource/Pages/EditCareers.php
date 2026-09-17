<?php

namespace App\Filament\Resources\CareersResource\Pages;

use App\Filament\Resources\CareersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareers extends EditRecord
{
    protected static string $resource = CareersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
