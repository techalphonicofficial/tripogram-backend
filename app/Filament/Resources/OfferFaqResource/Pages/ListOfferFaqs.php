<?php
namespace App\Filament\Resources\OfferFaqResource\Pages;
use App\Filament\Resources\OfferFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOfferFaqs extends ListRecords
{
    protected static string $resource = OfferFaqResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
