<?php
namespace App\Filament\Resources\PopupSettingResource\Pages;
use App\Filament\Resources\PopupSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListPopupSettings extends ListRecords
{
    protected static string $resource = PopupSettingResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
