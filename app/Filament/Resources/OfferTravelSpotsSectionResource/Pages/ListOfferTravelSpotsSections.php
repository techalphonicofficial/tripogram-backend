<?php
namespace App\Filament\Resources\OfferTravelSpotsSectionResource\Pages;
use App\Filament\Resources\OfferTravelSpotsSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOfferTravelSpotsSections extends ListRecords
{
    protected static string $resource = OfferTravelSpotsSectionResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
