<?php

namespace App\Filament\Resources\PageAddonSchemasResource\Pages;

use App\Filament\Resources\PageAddonSchemasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPageAddonSchemas extends ListRecords
{
    protected static string $resource = PageAddonSchemasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
