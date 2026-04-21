<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbacksResource\Pages;
use App\Models\Feedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class FeedbacksResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Enquiries';
    protected static ?int $navigationSort = 4;

    // ✅ Navigation Permission Logic
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Admin bypass
        if ($user->role === 'admin') {
            return true;
        }

        $role = Role::where('name', $user->role)->first();

        if (!$role) {
            return false;
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

        return in_array('feedbacks', $permissionResources);
    }

    // ✅ Form (Create / Edit)
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('phone')
                ->required()
                ->tel(),

            Forms\Components\Textarea::make('message')
                ->label('Feedback')
                ->required()
                ->rows(5)
                ->columnSpanFull(),

            Forms\Components\Select::make('rating')
                ->options([
                    1 => '⭐',
                    2 => '⭐⭐',
                    3 => '⭐⭐⭐',
                    4 => '⭐⭐⭐⭐',
                    5 => '⭐⭐⭐⭐⭐',
                ])
                ->required(),

            Forms\Components\TextInput::make('booking_id')
                ->numeric()
                ->label('Booking ID')
                ->nullable(),
        ]);
    }

    // ✅ Table View
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Feedback')
                    ->limit(40),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating'),

                Tables\Columns\TextColumn::make('booking_id')
                    ->label('Booking ID'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i'),
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

    // ✅ Relations (optional future use)
    public static function getRelations(): array
    {
        return [];
    }

    // ✅ Pages
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedbacks::route('/'),
            'create' => Pages\CreateFeedbacks::route('/create'),
            'edit' => Pages\EditFeedbacks::route('/{record}/edit'),
        ];
    }
}