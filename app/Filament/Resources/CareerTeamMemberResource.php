<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerTeamMemberResource\Pages;
use App\Models\CareerTeamMember;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{FileUpload, TextInput, Toggle, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareerTeamMemberResource extends Resource
{
    protected static ?string $model = CareerTeamMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Team Members';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->role == 'admin') return true;

        $role = Role::where('name', $user->role)->first();
        if (!$role) return false;

        $rolePermissionIds = DB::table('role_has_permissions')->where('role_id', $role->id)->pluck('permission_id');
        if ($rolePermissionIds->isEmpty()) return false;

        $permissionResources = DB::table('permissions')->whereIn('id', $rolePermissionIds)->pluck('resource')->toArray();
        return in_array('career-team-members', $permissionResources) || in_array('careers', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Team Member Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->placeholder('e.g. John Doe')
                        ->maxLength(255),

                    TextInput::make('designation')
                        ->label('Designation')
                        ->placeholder('e.g. Explorer / Sales Lead')
                        ->maxLength(255),

                    FileUpload::make('photo')
                        ->label('Photo / Avatar')
                        ->image()
                        ->directory('careers/team')
                        ->avatar(),

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
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
                Tables\Columns\ImageColumn::make('photo')->label('Avatar')->circular()->disk('public'),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('designation')->label('Designation')->searchable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
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
            'index'  => Pages\ListCareerTeamMembers::route('/'),
            'create' => Pages\CreateCareerTeamMember::route('/create'),
            'edit'   => Pages\EditCareerTeamMember::route('/{record}/edit'),
        ];
    }
}
