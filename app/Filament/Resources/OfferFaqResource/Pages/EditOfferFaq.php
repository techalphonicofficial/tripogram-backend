<?php
namespace App\Filament\Resources\OfferFaqResource\Pages;
use App\Filament\Resources\OfferFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOfferFaq extends EditRecord
{
    protected static string $resource = OfferFaqResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
