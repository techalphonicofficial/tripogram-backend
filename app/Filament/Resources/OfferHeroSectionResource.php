<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferHeroSectionResource\Pages;
use App\Models\OfferHeroSection;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferHeroSectionResource extends Resource
{
    protected static ?string $model = OfferHeroSection::class;

    protected static ?string $navigationIcon    = 'heroicon-o-ticket';
    protected static ?string $navigationGroup   = 'Offer Management';
    protected static ?string $navigationLabel   = 'Offers Hero Section';
    protected static ?int    $navigationSort    = 1;

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

        return in_array('offer-hero-sections', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Navbar & Header Text')
                ->schema([
                    TextInput::make('navbar_text')
                        ->label('Navbar Link Text')
                        ->placeholder('e.g. Offers / Exclusive Offers')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('small_label')
                        ->label('Small Top Label')
                        ->placeholder('e.g. EXCLUSIVE TRAVEL OFFERS')
                        ->maxLength(255),

                    TextInput::make('heading')
                        ->label('Main Banner Heading')
                        ->placeholder('e.g. Exclusive Travel Offers')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Banner Description / Subtext')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Background Image & Status')
                ->schema([
                    FileUpload::make('background_image')
                        ->label('Banner Background Image')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->deletable(true)
                        ->directory('offers/hero'),

                    Toggle::make('is_active')
                        ->label('Show Offers on Website (YES / NO)')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true)
                        ->helperText('YES = Offers link & page will show on website. NO = Offers will be hidden.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width('60px'),
                Tables\Columns\TextColumn::make('navbar_text')->label('Navbar Text'),
                Tables\Columns\TextColumn::make('heading')->label('Heading')->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->limit(50),
                Tables\Columns\ImageColumn::make('background_image')->label('BG Image')->disk('public'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Website Visible (YES/NO)'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOfferHeroSections::route('/'),
            'create' => Pages\CreateOfferHeroSection::route('/create'),
            'edit'   => Pages\EditOfferHeroSection::route('/{record}/edit'),
        ];
    }
}
