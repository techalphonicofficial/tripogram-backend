<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageAddonSchemasResource\Pages;
use App\Filament\Resources\PageAddonSchemasResource\RelationManagers;
use App\Models\PageAddonSchemas;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;


class PageAddonSchemasResource extends Resource
{
    protected static ?string $model = PageAddonSchemas::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 3;
    
    
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




        
      if (!in_array('page-addon-schemas', $permissionResources)) {
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
                Select::make('page_id')
                    ->label('Page')
                    ->relationship('page', 'name') // assuming "title" column in pages table
                    ->required(),
                TextInput::make('schema_type')
                    ->label('Schema Type')->unique(column: 'schema_type', ignoreRecord: true)
                    ->required()
                    ->maxLength(100),
                Textarea::make('schema')
                    ->label('Schema JSON')
                  
                    ->required()
                  
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('page.name')->label('Page')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('schema_type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageAddonSchemas::route('/'),
            'create' => Pages\CreatePageAddonSchemas::route('/create'),
            'edit' => Pages\EditPageAddonSchemas::route('/{record}/edit'),
        ];
    }
}
