<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageAddonSchemasResource\Pages;
use App\Models\PackageAddonSchemas;
use App\Models\Packages;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class PackageAddonSchemasResource extends Resource
{
    protected static ?string $model = PackageAddonSchemas::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if ($user->role == 'admin') {
            return true;
        }

        $role = Role::where('name', $user->role)->first();

        if (!$role) {
            abort(403, 'Role not found');
        }

        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id');

        if ($rolePermissionIds->isEmpty()) {
            return false;
        }

        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        return in_array('package-addon-schemas', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('package_id')
                ->label('Package')
                ->options(fn () => Packages::all()->pluck('title', 'id'))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $package = Packages::find($state);
                        $set('package_slug_display', $package?->slug);
                    } else {
                        $set('package_slug_display', null);
                    }
                }),

            Forms\Components\Placeholder::make('package_slug_display')
                ->label('Package Slug')
                ->content(function ($get) {
                    $package = Packages::find($get('package_id'));
                    return $package?->slug ?? '-';
                }),

            // ❌ schema_type hidden (no UI)
            Forms\Components\Hidden::make('schema_type')
                ->default('WebSite'),

            Textarea::make('schema')
                ->label('Schema JSON')
                ->rules(['json'])
                ->required()
                ->autosize()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),

                Tables\Columns\TextColumn::make('package.title')
                    ->label('Package Title')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('package.slug')
                    ->label('Package Slug')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('schema_type')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('package_id')
                    ->label('Filter by Package')
                    ->options(fn () => Packages::all()->pluck('title', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('schema_type')
                    ->label('Filter by Schema Type')
                    ->options([
                        'WebSite' => 'WebSite',
                        'WebPage' => 'WebPage',
                        'Organization' => 'Organization',
                        'LocalBusiness' => 'LocalBusiness',
                        'BreadcrumbList' => 'BreadcrumbList',
                        'Article' => 'Article',
                        'BlogPosting' => 'BlogPosting',
                        'FAQPage' => 'FAQPage',
                        'TouristAttraction' => 'TouristAttraction',
                        'Trip' => 'Trip',
                        'Event' => 'Event',
                        'Hotel' => 'Hotel',
                        'Restaurant' => 'Restaurant',
                        'Review' => 'Review',
                        'AggregateRating' => 'AggregateRating',
                        'Offer' => 'Offer',
                        'AggregateOffer' => 'AggregateOffer',
                        'VideoObject' => 'VideoObject',
                        'ImageObject' => 'ImageObject',
                    ])
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackageAddonSchemas::route('/'),
            'create' => Pages\CreatePackageAddonSchemas::route('/create'),
            'edit' => Pages\EditPackageAddonSchemas::route('/{record}/edit'),
        ];
    }
}