<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;
class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck'; // better icon for vehicle

    protected static ?string $navigationLabel = 'Vehicles';
    protected static ?string $pluralModelLabel = 'Vehicles';
    protected static ?string $modelLabel = 'Vehicle';
    protected static ?string $navigationGroup = 'Vehicles Management';
    protected static ?int $navigationSort = 1;
    
    
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




        
      if (!in_array('vehicles', $permissionResources)) {
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
                Forms\Components\Select::make('vehicle_type')
                    ->label('Vehicle Type')
                    ->options([
                        'tempo traveller' => 'Tempo Traveller',
                        'bus' => 'Bus',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('vehicle_seats')
                    ->label('Number of Seats')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                Forms\Components\Select::make('vehicle_row')
                    ->label('Seat Row Format')
                    ->options([
                        '1-2' => '1-2',
                        '2-2' => '2-2',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle_seats')
                    ->label('Seats')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle_row')
                    ->label('Row Format')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Created'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->label('Updated'),
            ])
            ->filters([])
            ->actions([
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
            // 'view' => Pages\ViewVehicle::route('/{record}'),
        ];
    }
}
