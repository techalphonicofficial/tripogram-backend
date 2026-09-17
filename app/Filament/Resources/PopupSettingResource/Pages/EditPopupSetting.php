<?php
namespace App\Filament\Resources\PopupSettingResource\Pages;
use App\Filament\Resources\PopupSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditPopupSetting extends EditRecord
{
    protected static string $resource = PopupSettingResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
