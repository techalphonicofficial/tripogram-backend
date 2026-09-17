<?php
namespace App\Filament\Resources\OfferFaqSectionResource\Pages;
use App\Filament\Resources\OfferFaqSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOfferFaqSections extends ListRecords
{
    protected static string $resource = OfferFaqSectionResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
