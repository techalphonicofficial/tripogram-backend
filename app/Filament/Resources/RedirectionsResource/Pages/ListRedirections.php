<?php

namespace App\Filament\Resources\RedirectionsResource\Pages;

use App\Filament\Resources\RedirectionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRedirections extends ListRecords
{
    protected static string $resource = RedirectionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
