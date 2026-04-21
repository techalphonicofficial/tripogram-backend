<?php

namespace App\Filament\Resources\PopupFormsResource\Pages;

use App\Filament\Resources\PopupFormsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPopupForms extends EditRecord
{
    protected static string $resource = PopupFormsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
