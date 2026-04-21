<?php

namespace App\Filament\Resources\PackagesResource\Pages;

use App\Filament\Resources\PackagesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePackages extends CreateRecord
{
    protected static string $resource = PackagesResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(), // सिर्फ Create वाला button
            $this->getCancelFormAction(), // Cancel button
        ];
    }
}
