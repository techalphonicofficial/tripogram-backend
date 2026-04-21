<?php

namespace App\Filament\Resources\PackageAddonSchemasResource\Pages;

use App\Filament\Resources\PackageAddonSchemasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackageAddonSchemas extends ListRecords
{
    protected static string $resource = PackageAddonSchemasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
