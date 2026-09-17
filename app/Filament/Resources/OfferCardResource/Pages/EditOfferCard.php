<?php
namespace App\Filament\Resources\OfferCardResource\Pages;
use App\Filament\Resources\OfferCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOfferCard extends EditRecord
{
    protected static string $resource = OfferCardResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
