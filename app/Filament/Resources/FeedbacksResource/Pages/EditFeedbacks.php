<?php

namespace App\Filament\Resources\FeedbacksResource\Pages;

use App\Filament\Resources\FeedbacksResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeedbacks extends EditRecord
{
    protected static string $resource = FeedbacksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
