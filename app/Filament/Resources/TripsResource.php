<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripsResource\Pages;
use App\Filament\Resources\TripsResource\RelationManagers;
use App\Models\Trips;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;

class TripsResource extends Resource
{
    protected static ?string $model = Trips::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Travel Management';
    protected static ?int $navigationSort = 1;
      protected static ?string $modelLabel = 'Category Trip';
    protected static ?string $pluralModelLabel = 'Category Trip';
    protected static ?string $navigationLabel = 'Trips Category';
    public static function getNavigationBadge(): ?string
    {
        return (string) Trips::count();
    }
    
       public static function shouldRegisterNavigation(): bool
    {


        $user = Auth::user();
// dd($user);
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
        
        
        // return $rolePermissionIds;

              
        
        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();




        
      if (!in_array('trips', $permissionResources)) {
         return false;
        }

       

        return true;
    
        // return false;
        // dd('sd');
        return auth()->user()?->role === 'admin';

    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('thumbnail')
                    ->image()
                    ->directory('trip-thumbnail')
                    ->required(),
                FileUpload::make('banner')
                    ->image()
                    ->directory('trip-banner')
                    ->required(),
                TextInput::make('heading')
                    ->required()
                    ->live(onBlur: true)
                   
                    ->maxLength(191),
                TextInput::make('slug')
                  
                    ->required()
                    ->label('Trip Slug'),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('international')->label('Is International Trips??')->default(false)->columnSpanFull(),
                Toggle::make('show_in_home')->label('Show this trip in Home??')->default(false)->columnSpanFull(),
                Toggle::make('show_in_menu')->label('Show this trip in Menu??')->default(false)->columnSpanFull(),
                Textarea::make('meta_title')
                    ->maxLength(191)->columnSpanFull(),
                Textarea::make('meta_description')
                    ->rows(4)->columnSpanFull(),
                Textarea::make('meta_keywords')
                    ->maxLength(191)->columnSpanFull(),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('heading')->searchable()->sortable(),
                TextColumn::make('packages_count')
                    ->counts('packages')
                    ->label('No. of Packages')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                    Tables\Columns\ToggleColumn::make('show_in_home')
                ->label('Show in Home'),
            ])
            ->filters([
                //
            ])
            
            ->actions([
                Tables\Actions\Action::make('viewPackages')
                ->label('View Packages')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(fn ($record) => url("/admin/packages?tableFilters[trip_id][value]={$record->id}"))
                ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
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
        return [
            //
        ];
    }
   protected static function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Trip'), // 👈 yahan text change
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            // 'create' => Pages\CreateTrips::route('/create'),
            'edit' => Pages\EditTrips::route('/{record}/edit'),
        ];
    }
}
