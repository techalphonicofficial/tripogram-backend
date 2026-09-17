<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerWhyJoinCardResource\Pages;
use App\Models\CareerWhyJoinCard;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerWhyJoinCardResource extends Resource
{
    protected static ?string $model = CareerWhyJoinCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Why Join Us Cards';
    protected static ?int $navigationSort = 6;

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
        return in_array('career-why-join-cards', $permissionResources) || in_array('careers', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Why Join Us Card Details')
                ->schema([
                    TextInput::make('title')
                        ->label('Card Title')
                        ->required()
                        ->placeholder('e.g. Explore Together, Grow With Us')
                        ->maxLength(255),

                    TextInput::make('icon')
                        ->label('Icon (Identifier or Image Upload)')
                        ->placeholder('e.g. users, chart, trophy, globe')
                        ->helperText('Specify icon name (users, chart, trophy, globe, etc.) or upload image below'),

                    FileUpload::make('icon_image')
                        ->label('Icon Image Upload (Optional)')
                        ->image()
                        ->directory('careers/why-join-cards')
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('icon', $state);
                            }
                        }),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

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
                Tables\Columns\TextColumn::make('title')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('icon')->label('Icon'),
                Tables\Columns\TextColumn::make('description')->label('Description')->limit(50),
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
            'index'  => Pages\ListCareerWhyJoinCards::route('/'),
            'create' => Pages\CreateCareerWhyJoinCard::route('/create'),
            'edit'   => Pages\EditCareerWhyJoinCard::route('/{record}/edit'),
        ];
    }
}
