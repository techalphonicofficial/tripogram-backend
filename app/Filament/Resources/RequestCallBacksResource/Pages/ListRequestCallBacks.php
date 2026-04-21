<?php

namespace App\Filament\Resources\RequestCallBacksResource\Pages;

use App\Filament\Resources\RequestCallBacksResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestCallBacks extends ListRecords
{
    protected static string $resource = RequestCallBacksResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
