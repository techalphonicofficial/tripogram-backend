<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MainPagesResource\Pages;
use App\Filament\Resources\MainPagesResource\RelationManagers\PageSectionsRelationManager;
use App\Models\MainPages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;
class MainPagesResource extends Resource
{
    protected static ?string $model = MainPages::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pages';
    
    
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




        
      if (!in_array('main-pages', $permissionResources)) {
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

                TextInput::make('name')
                    ->label('Page Name')
                    ->required()
                    ->maxLength(255)->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),

                TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->maxLength(255)->required(),
                    
                TextInput::make('meta_keywords')
                    ->label('Meta Keywords')
                    ->maxLength(255)->required(),
                    
                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(3)->required(),
         
                

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Page Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PageSectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMainPages::route('/'),
            'create' => Pages\CreateMainPages::route('/create'),
            'edit' => Pages\EditMainPages::route('/{record}/edit'),
        ];
    }
}
