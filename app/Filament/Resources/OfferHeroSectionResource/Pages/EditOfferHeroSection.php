<?php
namespace App\Filament\Resources\OfferHeroSectionResource\Pages;
use App\Filament\Resources\OfferHeroSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOfferHeroSection extends EditRecord
{
    protected static string $resource = OfferHeroSectionResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
