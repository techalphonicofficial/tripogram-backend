<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerWhyJoinSectionResource\Pages;
use App\Models\CareerWhyJoinSection;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerWhyJoinSectionResource extends Resource
{
    protected static ?string $model = CareerWhyJoinSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Why Join Us';
    protected static ?int $navigationSort = 5;

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
        return in_array('career-why-join-sections', $permissionResources) || in_array('careers', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Why Join Us Main Section Details')
                ->schema([
                    TextInput::make('small_label')
                        ->label('Small Label')
                        ->placeholder("e.g. WHY YOU'LL LOVE IT HERE")
                        ->maxLength(255),

                    TextInput::make('heading')
                        ->label('Main Heading')
                        ->placeholder("e.g. More Than a Workplace, It's a Community")
                        ->maxLength(255),

                    TextInput::make('highlight_text')
                        ->label('Highlighted Text / Word')
                        ->placeholder('e.g. Community')
                        ->maxLength(255)
                        ->helperText('The specific word/text to highlight in the heading on the frontend'),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    FileUpload::make('background_image')
                        ->label('Background Image')
                        ->image()
                        ->directory('careers/why-join'),

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
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('small_label')->label('Small Label')->searchable(),
                Tables\Columns\TextColumn::make('heading')->label('Heading')->searchable(),
                Tables\Columns\TextColumn::make('highlight_text')->label('Highlight Word')->badge()->color('warning'),
                Tables\Columns\ImageColumn::make('background_image')->label('BG Image')->disk('public'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCareerWhyJoinSections::route('/'),
            'create' => Pages\CreateCareerWhyJoinSection::route('/create'),
            'edit'   => Pages\EditCareerWhyJoinSection::route('/{record}/edit'),
        ];
    }
}
