<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PopupSettingResource\Pages;
use App\Models\PopupSetting;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Toggle, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PopupSettingResource extends Resource
{
    protected static ?string $model = PopupSetting::class;

    protected static ?string $navigationIcon    = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup   = 'Content Management';
    protected static ?string $navigationLabel   = 'Popup Settings';
    protected static ?int    $navigationSort    = 15;

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

        return in_array('popup-settings', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Popup Modal Headings & Buttons')
                ->schema([
                    TextInput::make('form_heading')
                        ->label('Form Heading')
                        ->placeholder('e.g. Plan your Next Trip')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('submit_button_text')
                        ->label('Submit Button Text')
                        ->placeholder('e.g. Submit')
                        ->required()
                        ->maxLength(255),

                    Toggle::make('is_active')
                        ->label('Popup Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width('60px'),
                Tables\Columns\TextColumn::make('form_heading')->label('Heading')->searchable(),
                Tables\Columns\TextColumn::make('submit_button_text')->label('Button Text'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPopupSettings::route('/'),
            'create' => Pages\CreatePopupSetting::route('/create'),
            'edit'   => Pages\EditPopupSetting::route('/{record}/edit'),
        ];
    }
}
