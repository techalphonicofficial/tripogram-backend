<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerSettingsResource\Pages;
use App\Models\CareerSetting;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerSettingsResource extends Resource
{
    protected static ?string $model = CareerSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Career Page Settings';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->role == 'admin') {
            return true;
        }

        $role = Role::where('name', $user->role)->first();
        if (!$role) {
            return false;
        }

        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id');

        if ($rolePermissionIds->isEmpty()) {
            return false;
        }

        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        return in_array('career-settings', $permissionResources);
    }

    // =============================================
    // FORM
    // =============================================

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Hero Section')
                ->schema([
                    TextInput::make('key')
                        ->label('Setting Key')
                        ->required()
                        ->maxLength(100)
                        ->unique(column: 'key', ignoreRecord: true)
                        ->helperText('Unique key identifier, e.g. hero_title'),

                    Textarea::make('value')
                        ->label('Value')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // =============================================
    // TABLE
    // =============================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('key')
                    ->label('Setting Key')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->value),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('key', 'asc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('+ Add Setting'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCareerSettings::route('/'),
            'create' => Pages\CreateCareerSetting::route('/create'),
            'edit'   => Pages\EditCareerSetting::route('/{record}/edit'),
        ];
    }
}
