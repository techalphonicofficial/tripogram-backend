<?php

namespace App\Filament\Resources\BlogsResource\Pages;

use App\Filament\Resources\BlogsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBlogs extends CreateRecord
{
    protected static string $resource = BlogsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate slug
        $data['slug'] = Str::slug($data['heading']);
        return $data;
    }
}
