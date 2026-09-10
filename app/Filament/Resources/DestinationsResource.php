<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationsResource\Pages;
use App\Models\Destinations;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;

class DestinationsResource extends Resource
{
    protected static ?string $model = Destinations::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Travel Management';
    protected static ?int $navigationSort = 2;


    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Admin & Manager bypass
        if ($user->role == 'admin') {
            return true;
        }

       
        $role = Role::where('name', $user->role)->first();
        $role_id = $role->id;
        if (!$role_id) {
            return false;
        }
        
        if (!$role) {
            abort(403, 'Role not found');
        }
        
        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role_id)
            ->pluck('permission_id');

        if ($rolePermissionIds->isEmpty()) {
            return false;
        }
        
        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        if (!in_array('destinations', $permissionResources)) {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state)))
                    ->required()
                    ->label('Destination Name'),
                
                // ✅ ADD STATE CODE FIELD
                Forms\Components\TextInput::make('state_code')
                    ->label('State Code')
                    ->maxLength(10)
                    ->required()
                    ->helperText('Example: DL, UK, HP, RJ, UP, PB, HR, MP, GJ, MH, WB, KA, TN, KL')
                    ->placeholder('Enter state code (e.g., DL for Delhi)'),
                
                Forms\Components\RichEditor::make('content')
                    ->label('Content')
     
                    ->columnSpanFull(),
                
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(column: 'slug', ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\FileUpload::make('thumbnail')
                    ->image()
                    ->directory('destinations/thumbnails')
                    ->required(),

                Forms\Components\FileUpload::make('banner')
                    ->image()
                    ->directory('destinations/banners')
                    ->required(),

                Forms\Components\TextInput::make('meta_title')
                    ->maxLength(255),

                Forms\Components\Textarea::make('meta_description'),

                Forms\Components\TextInput::make('meta_keywords')
                    ->maxLength(255),
Textarea::make('meta_schema')
    ->label('Meta Schema (JSON)')
    ->rows(8)
    ->columnSpanFull(),

Repeater::make('faq')
    ->schema([
        TextInput::make('question')
            ->required()
            ->maxLength(255),

        Textarea::make('answer')
            ->required()
            ->rows(3),
    ])
    ->columnSpanFull()
    ->collapsible()
    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\Toggle::make('show_in_home')
                    ->label('Show in Home')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                // ✅ ADD STATE CODE COLUMN IN TABLE
                Tables\Columns\TextColumn::make('state_code')
                    ->label('State Code')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->width('100px')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\ToggleColumn::make('show_in_home')
                    ->label('Show in Home Page??'),
                    
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                // ✅ ADD STATE CODE FILTER
                Tables\Filters\SelectFilter::make('state_code')
                    ->label('State Code')
                    ->options([
                        'DL' => 'Delhi',
                        'UK' => 'Uttarakhand',
                        'HP' => 'Himachal Pradesh',
                        'RJ' => 'Rajasthan',
                        'UP' => 'Uttar Pradesh',
                        'PB' => 'Punjab',
                        'HR' => 'Haryana',
                        'MP' => 'Madhya Pradesh',
                        'GJ' => 'Gujarat',
                        'MH' => 'Maharashtra',
                        'WB' => 'West Bengal',
                        'KA' => 'Karnataka',
                        'TN' => 'Tamil Nadu',
                        'KL' => 'Kerala',
                    ])
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewPackages')
                    ->label('View Packages')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn ($record) => url("/admin/packages?tableFilters[destination_id][value]={$record->id}"))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestinations::route('/create'),
            'edit' => Pages\EditDestinations::route('/{record}/edit'),
        ];
    }
}