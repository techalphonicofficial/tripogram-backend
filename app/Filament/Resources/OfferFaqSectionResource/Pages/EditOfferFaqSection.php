<?php
namespace App\Filament\Resources\OfferFaqSectionResource\Pages;
use App\Filament\Resources\OfferFaqSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOfferFaqSection extends EditRecord
{
    protected static string $resource = OfferFaqSectionResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
