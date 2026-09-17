<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PopupSlideResource\Pages;
use App\Models\PopupSlide;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Textarea, Toggle, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PopupSlideResource extends Resource
{
    protected static ?string $model = PopupSlide::class;

    protected static ?string $navigationIcon    = 'heroicon-o-photo';
    protected static ?string $navigationGroup   = 'Content Management';
    protected static ?string $navigationLabel   = 'Popup Slides & Banner';
    protected static ?int    $navigationSort    = 16;

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

        return in_array('popup-slides', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Popup Slide Content & Image')
                ->schema([
                    TextInput::make('title')
                        ->label('Overlay Title')
                        ->placeholder('e.g. Your Next Adventure')
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Overlay Subtext / Description')
                        ->placeholder('e.g. Handpicked experiences, made simple by Tripogram.')
                        ->rows(3)
                        ->columnSpanFull(),

                    FileUpload::make('image')
                        ->label('Background Image')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->imageEditor()
                        ->deletable(true)
                        ->directory('popup/slides')
                        ->helperText('Upload background image for popup left panel (Wildlife / Landscape photo)'),

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

                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')->disk('public')->size(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Subtext')->limit(50),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y')->sortable(),
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
            'index'  => Pages\ListPopupSlides::route('/'),
            'create' => Pages\CreatePopupSlide::route('/create'),
            'edit'   => Pages\EditPopupSlide::route('/{record}/edit'),
        ];
    }
}
