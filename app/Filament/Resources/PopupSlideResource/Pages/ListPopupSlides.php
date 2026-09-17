<?php
namespace App\Filament\Resources\PopupSlideResource\Pages;
use App\Filament\Resources\PopupSlideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListPopupSlides extends ListRecords
{
    protected static string $resource = PopupSlideResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
