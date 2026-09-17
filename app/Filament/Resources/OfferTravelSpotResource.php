<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferTravelSpotResource\Pages;
use App\Models\OfferTravelSpot;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Section, Select};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferTravelSpotResource extends Resource
{
    protected static ?string $model = OfferTravelSpot::class;

    protected static ?string $navigationIcon    = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup   = 'Offer Management';
    protected static ?string $navigationLabel   = 'Popular Travel Spots';
    protected static ?int    $navigationSort    = 4;

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

        return in_array('offer-travel-spots', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Travel Spot Package Details')
                ->schema([
                    Select::make('url')
                        ->label('Select Package / Destination')
                        ->options(function ($record) {
                            $options = [];

                            // 1. Packages
                            $packages = \App\Models\Packages::whereNotNull('slug')
                                ->where('slug', '!=', '')
                                ->orderBy('title')
                                ->get(['title', 'slug']);

                            $pkgGroup = [];
                            foreach ($packages as $pkg) {
                                $cleanSlug = ltrim($pkg->slug, '/');
                                $url = "/packages/{$cleanSlug}";
                                $pkgGroup[$url] = "📦 Package: " . $pkg->title;
                            }
                            if (!empty($pkgGroup)) {
                                $options['All Packages'] = $pkgGroup;
                            }

                            // 2. Destinations
                            $destinations = \App\Models\Destinations::whereNotNull('slug')
                                ->where('slug', '!=', '')
                                ->orderBy('name')
                                ->get(['name', 'slug']);

                            $destGroup = [];
                            foreach ($destinations as $dest) {
                                $cleanSlug = ltrim($dest->slug, '/');
                                $url = "/destinations/{$cleanSlug}";
                                $destGroup[$url] = "📍 Destination: " . $dest->name;
                            }
                            if (!empty($destGroup)) {
                                $options['All Destinations'] = $destGroup;
                            }

                            // Preserve existing record URL if not in list
                            if ($record && $record->url) {
                                $exists = false;
                                foreach ($options as $group) {
                                    if (is_array($group) && isset($group[$record->url])) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                if (!$exists) {
                                    $options['Custom / Current Link'] = [$record->url => "🔗 Custom: " . $record->url];
                                }
                            }

                            return $options;
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                if (str_contains($state, '/packages/')) {
                                    $slug = last(explode('/', trim($state, '/')));
                                    $pkg = \App\Models\Packages::where('slug', $slug)->first();
                                    if ($pkg) {
                                        $set('title', $pkg->title);
                                    }
                                } elseif (str_contains($state, '/destinations/')) {
                                    $slug = last(explode('/', trim($state, '/')));
                                    $dest = \App\Models\Destinations::where('slug', $slug)->first();
                                    if ($dest) {
                                        $set('title', $dest->name);
                                    }
                                }
                            }
                        })
                        ->placeholder('Select Package or Destination...')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('title')
                        ->label('Spot Name / Title')
                        ->required()
                        ->placeholder('Auto-filled when package selected...')
                        ->maxLength(255),

                    TextInput::make('subtitle')
                        ->label('Subtitle / Tag (Optional)')
                        ->placeholder('e.g. Explore Packages, Starting @ ₹9,999')
                        ->maxLength(255),

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
                Tables\Columns\ImageColumn::make('display_image')
                    ->label('Package Image')
                    ->getStateUsing(function ($record) {
                        if ($record->url && str_contains($record->url, '/packages/')) {
                            $slug = last(explode('/', trim($record->url, '/')));
                            $pkg = \App\Models\Packages::where('slug', $slug)->first();
                            if ($pkg && $pkg->thumbnail) {
                                return str_replace(url('storage/'), '', $pkg->thumbnail);
                            }
                        } elseif ($record->url && str_contains($record->url, '/destinations/')) {
                            $slug = last(explode('/', trim($record->url, '/')));
                            $dest = \App\Models\Destinations::where('slug', $slug)->first();
                            if ($dest && $dest->thumbnail) {
                                return str_replace(url('storage/'), '', $dest->thumbnail);
                            }
                        }
                        return $record->image;
                    })
                    ->disk('public')
                    ->size(50),
                Tables\Columns\TextColumn::make('title')->label('Spot Title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('url')->label('URL Link')->limit(30),
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
            'index'  => Pages\ListOfferTravelSpots::route('/'),
            'create' => Pages\CreateOfferTravelSpot::route('/create'),
            'edit'   => Pages\EditOfferTravelSpot::route('/{record}/edit'),
        ];
    }
}
