<?php

namespace App\Filament\Resources\CareerTeamMemberResource\Pages;

use App\Filament\Resources\CareerTeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareerTeamMembers extends ListRecords
{
    protected static string $resource = CareerTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
