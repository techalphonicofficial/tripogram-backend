<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use App\Filament\Resources\SettingsResource;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingsResource::class;

    // 🔹 Proper redirect on mount
    public function mount(): void
    {
        // Redirect to first (or only) record edit page
        $firstRecord = \App\Models\Settings::firstOrCreate([]);
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $firstRecord->id]));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
