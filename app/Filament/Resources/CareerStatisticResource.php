<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerStatisticResource\Pages;
use App\Models\CareerStatistic;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerStatisticResource extends Resource
{
    protected static ?string $model = CareerStatistic::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Statistics';
    protected static ?int $navigationSort = 4;

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
        return in_array('career-statistics', $permissionResources) || in_array('careers', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Statistic Card Details')
                ->schema([
                    TextInput::make('number')
                        ->label('Number / Value')
                        ->required()
                        ->placeholder('e.g. 70+, 25+, 8+, 4.8 / 5')
                        ->maxLength(255),

                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->placeholder('e.g. Team Members, Destinations')
                        ->maxLength(255),

                    TextInput::make('icon')
                        ->label('Icon (Name or SVG upload path)')
                        ->placeholder('e.g. users, location, briefcase, star')
                        ->helperText('You can specify an icon identifier (users, location, etc.) or upload an image below'),

                    FileUpload::make('icon_image')
                        ->label('Icon Image Upload (Optional)')
                        ->image()
                        ->directory('careers/statistics')
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('icon', $state);
                            }
                        }),

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
                Tables\Columns\TextColumn::make('number')->label('Number')->searchable()->badge()->color('primary'),
                Tables\Columns\TextColumn::make('title')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('icon')->label('Icon'),
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
            'index'  => Pages\ListCareerStatistics::route('/'),
            'create' => Pages\CreateCareerStatistic::route('/create'),
            'edit'   => Pages\EditCareerStatistic::route('/{record}/edit'),
        ];
    }
}
