<?php

namespace App\Filament\Resources;

use App\Models\Batch;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('batch_name')
                ->required()
                ->maxLength(255),
            Select::make('is_active')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive'
                ])
                ->default('active')
                ->required(),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('batch_name')->searchable()->sortable(),
                BadgeColumn::make('is_active')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->actions([
                ViewAction::make(),     // ✅ View button
                EditAction::make(),     // ✅ Edit button
                DeleteAction::make(),   // ✅ Delete button
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\BatchResource\Pages\ListBatches::route('/'),
            'create' => \App\Filament\Resources\BatchResource\Pages\CreateBatch::route('/create'),
            'edit' => \App\Filament\Resources\BatchResource\Pages\EditBatch::route('/{record}/edit'),
            
        ];
    }
}