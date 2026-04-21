<?php

namespace App\Filament\Resources\PopupFormsResource\Pages;

use App\Filament\Resources\PopupFormsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPopupForms extends ListRecords
{
    protected static string $resource = PopupFormsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    // 👇 Add Livewire listeners for real-time table refresh
    protected function getListeners(): array
    {
        return [
            'popupFormCreated' => 'refreshTable',
        ];
    }

    // 👇 Method called when event is fired
    public function refreshTable(): void
    {
        $this->table->refresh();
    }
}
