<?php

namespace App\Filament\Resources\MainPagesResource\Pages;

use App\Filament\Resources\MainPagesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainPages extends EditRecord
{
    protected static string $resource = MainPagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
