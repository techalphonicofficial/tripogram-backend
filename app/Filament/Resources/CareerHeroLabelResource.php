<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerHeroLabelResource\Pages;
use App\Models\CareerHeroLabel;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Select, TextInput, Toggle, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerHeroLabelResource extends Resource
{
    protected static ?string $model = CareerHeroLabel::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Hero Labels';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->role == 'admin') return true;

        $role = Role::where('name', $user->role)->first();
        if (!$role) return false;

        $rolePermissionIds = DB::table('role_has_permissions')->where('role_id', $role->id)->pluck('permission_id');
        if ($rolePermissionIds->isEmpty()) return false;

        $permissionResources = DB::table('permissions')->whereIn('id', $rolePermissionIds)->pluck('resource')->toArray();
        return in_array('career-hero-labels', $permissionResources) || in_array('careers', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Label Details')
                ->schema([
                    TextInput::make('text')
                        ->label('Label Text')
                        ->required()
                        ->placeholder('e.g. Team exploring')
                        ->maxLength(255),

                    Select::make('position')
                        ->label('Position')
                        ->options([
                            'top_left'     => 'Top Left',
                            'top_center'   => 'Top Center',
                            'top_right'    => 'Top Right',
                            'left_center'  => 'Left Center',
                            'right_center' => 'Right Center',
                            'bottom_left'  => 'Bottom Left',
                            'bottom_right' => 'Bottom Right',
                        ]),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('text')->label('Label Text')->searchable(),
                Tables\Columns\TextColumn::make('position')->label('Position')->badge()->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCareerHeroLabels::route('/'),
            'create' => Pages\CreateCareerHeroLabel::route('/create'),
            'edit'   => Pages\EditCareerHeroLabel::route('/{record}/edit'),
        ];
    }
}
