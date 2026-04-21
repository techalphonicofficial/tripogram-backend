<?php

namespace App\Filament\Resources\PageAddonSchemasResource\Pages;

use App\Filament\Resources\PageAddonSchemasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPageAddonSchemas extends EditRecord
{
    protected static string $resource = PageAddonSchemasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
