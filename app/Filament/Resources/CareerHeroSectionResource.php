<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerHeroSectionResource\Pages;
use App\Models\CareerHeroSection;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, ColorPicker, TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerHeroSectionResource extends Resource
{
    protected static ?string $model = CareerHeroSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Hero Section';
    protected static ?int $navigationSort = 1;

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
        return in_array('career-hero-sections', $permissionResources) || in_array('careers', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Hero Headings & Text')
                ->schema([
                    TextInput::make('label')
                        ->label('Section Label')
                        ->placeholder('e.g. CAREERS')
                        ->maxLength(255),

                    TextInput::make('heading_line_1')
                        ->label('Heading Line 1')
                        ->placeholder('e.g. New Places.')
                        ->maxLength(255),

                    TextInput::make('heading_line_2')
                        ->label('Heading Line 2')
                        ->placeholder('e.g. New Challenges.')
                        ->maxLength(255),

                    TextInput::make('heading_line_3')
                        ->label('Heading Line 3')
                        ->placeholder('e.g. Limitless Growth.')
                        ->maxLength(255),

                    ColorPicker::make('heading_highlight_color')
                        ->label('Heading Highlight Color')
                        ->default('#009ED1'),

                    TextInput::make('team_text')
                        ->label('Team Text')
                        ->placeholder('e.g. 40+ amazing people building experiences')
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Main Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('sub_text')
                        ->label('Sub Text')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Call to Action Button')
                ->schema([
                    TextInput::make('button_text')
                        ->label('Button Text')
                        ->placeholder('e.g. Explore Open Positions'),

                    TextInput::make('button_url')
                        ->label('Button URL')
                        ->placeholder('e.g. /careers/jobs'),
                ])->columns(2),

            Section::make('Hero Images')
                ->schema([
                    FileUpload::make('main_image')
                        ->label('Main Hero Image')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->deletable(true)
                        ->directory('careers/hero'),

                    FileUpload::make('secondary_image')
                        ->label('Secondary / Shape Image')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->deletable(true)
                        ->directory('careers/hero'),

                    FileUpload::make('background_image')
                        ->label('Background Pattern Image')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->deletable(true)
                        ->directory('careers/hero'),

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
                Tables\Columns\TextColumn::make('label')->label('Label')->searchable(),
                Tables\Columns\TextColumn::make('heading_line_1')->label('Heading 1')->searchable(),
                Tables\Columns\TextColumn::make('heading_line_2')->label('Heading 2')->searchable(),
                Tables\Columns\TextColumn::make('heading_line_3')->label('Heading 3')->searchable(),
                Tables\Columns\ColorColumn::make('heading_highlight_color')->label('Highlight Color'),
                Tables\Columns\ImageColumn::make('main_image')->label('Main Image')->disk('public'),
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
            'index'  => Pages\ListCareerHeroSections::route('/'),
            'create' => Pages\CreateCareerHeroSection::route('/create'),
            'edit'   => Pages\EditCareerHeroSection::route('/{record}/edit'),
        ];
    }
}
