<?php
namespace App\Filament\Resources\OfferTravelSpotResource\Pages;
use App\Filament\Resources\OfferTravelSpotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOfferTravelSpots extends ListRecords
{
    protected static string $resource = OfferTravelSpotResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
