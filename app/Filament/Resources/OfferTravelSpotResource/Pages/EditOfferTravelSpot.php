<?php
namespace App\Filament\Resources\OfferTravelSpotResource\Pages;
use App\Filament\Resources\OfferTravelSpotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOfferTravelSpot extends EditRecord
{
    protected static string $resource = OfferTravelSpotResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
