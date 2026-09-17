<?php
namespace App\Filament\Resources\PartnershipSectionResource\Pages;
use App\Filament\Resources\PartnershipSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditPartnershipSection extends EditRecord
{
    protected static string $resource = PartnershipSectionResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
