<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnershipSectionResource\Pages;
use App\Models\PartnershipSection;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartnershipSectionResource extends Resource
{
    protected static ?string $model = PartnershipSection::class;

    protected static ?string $navigationIcon    = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup   = 'Content Management';
    protected static ?string $navigationLabel   = 'Partnership Section';
    protected static ?int    $navigationSort    = 10;

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

        return in_array('partnership-sections', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Section Heading & Text')
                ->schema([
                    TextInput::make('small_label')
                        ->label('Small Label (Top)')
                        ->placeholder('e.g. TRUSTED BY & RECOGNIZED BY')
                        ->maxLength(255),

                    TextInput::make('heading')
                        ->label('Main Heading')
                        ->placeholder('e.g. Partnership & Recognition')
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Background & Status')
                ->schema([
                    FileUpload::make('background_image')
                        ->label('Background Image')
                        ->image()
                        ->disk('public')
                        ->directory('partnerships/section')
                        ->visibility('public'),

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
                Tables\Columns\TextColumn::make('id')->sortable()->width('60px'),
                Tables\Columns\TextColumn::make('small_label')->label('Label')->searchable(),
                Tables\Columns\TextColumn::make('heading')->label('Heading')->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->limit(60),
                Tables\Columns\ImageColumn::make('background_image')->label('BG')->disk('public'),
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
            'index'  => Pages\ListPartnershipSections::route('/'),
            'create' => Pages\CreatePartnershipSection::route('/create'),
            'edit'   => Pages\EditPartnershipSection::route('/{record}/edit'),
        ];
    }
}
