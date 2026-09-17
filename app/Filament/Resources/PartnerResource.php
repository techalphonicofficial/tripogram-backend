<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon    = 'heroicon-o-hand-raised';
    protected static ?string $navigationGroup   = 'Content Management';
    protected static ?string $navigationLabel   = 'Partners';
    protected static ?int    $navigationSort    = 11;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->role == 'admin') return true;

        $role = Role::where('name', $user->role)->first();
        if (!$role) return false;

        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role->id)->pluck('permission_id');
        if ($rolePermissionIds->isEmpty()) return false;

        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)->pluck('resource')->toArray();

        return in_array('partners', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Partner Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Partner Name')
                        ->required()
                        ->placeholder('e.g. MakeMyTrip, IndiGo, IATO')
                        ->maxLength(255),

                    TextInput::make('tag_line')
                        ->label('Tag Line / Description')
                        ->placeholder('e.g. Preferred Travel Partner')
                        ->maxLength(255),

                    TextInput::make('website_url')
                        ->label('Website URL')
                        ->url()
                        ->placeholder('https://...')
                        ->maxLength(500),

                    FileUpload::make('logo')
                        ->label('Partner Logo')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->imageEditor()
                        ->deletable(true)
                        ->directory('partnerships/logos')
                        ->helperText('Upload partner logo (PNG/SVG recommended with transparent background)'),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower number = appears first'),

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
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')->sortable()->width('60px'),

                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')->disk('public')->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Partner Name')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('tag_line')
                    ->label('Tag Line')->searchable()->limit(40),

                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')->limit(30)->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
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
            'index'  => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit'   => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
