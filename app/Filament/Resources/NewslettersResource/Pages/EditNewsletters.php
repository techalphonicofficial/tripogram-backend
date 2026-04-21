<?php

namespace App\Filament\Resources\NewslettersResource\Pages;

use App\Filament\Resources\NewslettersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewsletters extends EditRecord
{
    protected static string $resource = NewslettersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
