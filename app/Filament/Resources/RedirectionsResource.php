<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectionsResource\Pages;
use App\Models\Redirections;
use App\Models\Packages;
use App\Models\Trips;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;

class RedirectionsResource extends Resource
{
    protected static ?string $model = Redirections::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Redirections';
    protected static ?string $pluralLabel = 'Redirections';
    protected static ?string $modelLabel = 'Redirection';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 2;
    
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




        
      if (!in_array('redirections', $permissionResources)) {
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
                Forms\Components\Select::make('from_url')
    ->label('From Package')
    ->options(Packages::pluck('title', 'slug')) // slug save, title show
    ->searchable()
    ->required()
    ->columnSpan(2),

Forms\Components\Fieldset::make('Redirect To')
    ->schema([
        Forms\Components\Select::make('to_type')
            ->label('Type')
            ->options([
                'package' => 'Package',
                'trip'    => 'Trip',
            ])
            ->default('package')
            ->reactive()
            ->required()
            ->columnSpan(1),

        Forms\Components\Select::make('to_url')
            ->label('Select Destination')
            ->options(function (callable $get) {
                return $get('to_type') === 'trip'
                    ? Trips::pluck('heading', 'slug')   // slug save, heading show
                    : Packages::pluck('title', 'slug'); // slug save, title show
            })
            ->reactive()
            ->searchable()
            ->required()
            ->columnSpan(2),
    ])
    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_url')
                    ->label('From Package')
                    ->formatStateUsing(fn ($state) => Packages::where('slug', $state)->first()?->title)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('to_url')
                    ->label('Redirect To')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->to_type === 'trip') {
                            return 'Trip: ' . (Trips::where('slug', $state)->first()?->heading ?? '-');
                        }
                        return 'Package: ' . (Packages::where('slug', $state)->first()?->title ?? '-');
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('to_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'package',
                        'warning' => 'trip',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\ToggleColumn::make('status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('to_type')
                    ->label('Redirect Type')
                    ->options([
                        'package' => 'Package',
                        'trip'    => 'Trip',
                    ]),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
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
            'index'  => Pages\ListRedirections::route('/'),
            'create' => Pages\CreateRedirections::route('/create'),
            'edit'   => Pages\EditRedirections::route('/{record}/edit'),
        ];
    }
}
