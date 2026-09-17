<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferCardResource\Pages;
use App\Models\OfferCard;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferCardResource extends Resource
{
    protected static ?string $model = OfferCard::class;

    protected static ?string $navigationIcon    = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup   = 'Offer Management';
    protected static ?string $navigationLabel   = 'Offer Highlight Cards';
    protected static ?int    $navigationSort    = 2;

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

        return in_array('offer-cards', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Offer Card Details')
                ->schema([
                    TextInput::make('title')
                        ->label('Card Title')
                        ->required()
                        ->placeholder('e.g. Book at Just 999/- Only')
                        ->maxLength(255),

                    TextInput::make('icon')
                        ->label('Icon Name / Key')
                        ->placeholder('e.g. percent, tag, academic-cap, user-group')
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Card Description (Optional)')
                        ->rows(2)
                        ->columnSpanFull(),

                    FileUpload::make('icon_image')
                        ->label('Custom Icon Image (Optional)')
                        ->image()
                        ->disk('public')
                        ->directory('offers/cards')
                        ->visibility('public'),

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
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable()->width('60px'),
                Tables\Columns\ImageColumn::make('icon_image')->label('Icon Img')->disk('public'),
                Tables\Columns\TextColumn::make('title')->label('Card Title')->searchable(),
                Tables\Columns\TextColumn::make('icon')->label('Icon Key'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y')->sortable(),
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
            'index'  => Pages\ListOfferCards::route('/'),
            'create' => Pages\CreateOfferCard::route('/create'),
            'edit'   => Pages\EditOfferCard::route('/{record}/edit'),
        ];
    }
}
