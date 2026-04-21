<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewslettersResource\Pages;
use App\Models\Newsletters;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;
class NewslettersResource extends Resource
{
    protected static ?string $model = Newsletters::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Travel Management';
    
    
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




        
      if (!in_array('newsletters', $permissionResources)) {
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
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull(),

                Toggle::make('status')
                    ->label('Status')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                ToggleColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),
                TextColumn::make('created_at')->label('Created')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('status', true))
                    ->label('Active'),
            ])
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
            'index' => Pages\ListNewsletters::route('/'),
            'create' => Pages\CreateNewsletters::route('/create'),
            'edit' => Pages\EditNewsletters::route('/{record}/edit'),
        ];
    }
}
