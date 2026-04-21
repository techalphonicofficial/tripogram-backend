<?php

namespace App\Filament\Resources\RedirectionsResource\Pages;

use App\Filament\Resources\RedirectionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRedirections extends EditRecord
{
    protected static string $resource = RedirectionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
