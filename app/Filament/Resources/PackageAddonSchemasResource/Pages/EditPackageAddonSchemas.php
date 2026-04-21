<?php

namespace App\Filament\Resources\PackageAddonSchemasResource\Pages;

use App\Filament\Resources\PackageAddonSchemasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackageAddonSchemas extends EditRecord
{
    protected static string $resource = PackageAddonSchemasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
