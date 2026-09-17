<?php
namespace App\Filament\Resources\OfferCardResource\Pages;
use App\Filament\Resources\OfferCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOfferCards extends ListRecords
{
    protected static string $resource = OfferCardResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
