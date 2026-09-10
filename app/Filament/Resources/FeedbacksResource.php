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

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

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

    // FORM
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\TextInput::make('booking_id')
                ->numeric()
                ->required(),

		Forms\Components\Select::make('member_id')
   		 ->label('Member Name')
    		->relationship('infoGet', 'name')
    		->searchable()
    		->preload()
    		->required(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('contact')
                ->required()
                ->tel(),

            Forms\Components\TextInput::make('destination')
                ->required(),

            Forms\Components\DatePicker::make('departure_date'),

            Forms\Components\Select::make('travel_rating')
                ->options([
                    1 => '⭐',
                    2 => '⭐⭐',
                    3 => '⭐⭐⭐',
                    4 => '⭐⭐⭐⭐',
                    5 => '⭐⭐⭐⭐⭐',
                ]),

            Forms\Components\Select::make('stay_rating')
                ->options([
                    1 => '⭐',
                    2 => '⭐⭐',
                    3 => '⭐⭐⭐',
                    4 => '⭐⭐⭐⭐',
                    5 => '⭐⭐⭐⭐⭐',
                ]),

            Forms\Components\Select::make('meal_rating')
                ->options([
                    1 => '⭐',
                    2 => '⭐⭐',
                    3 => '⭐⭐⭐',
                    4 => '⭐⭐⭐⭐',
                    5 => '⭐⭐⭐⭐⭐',
                ]),

            Forms\Components\Select::make('captain_rating')
                ->options([
                    1 => '⭐',
                    2 => '⭐⭐',
                    3 => '⭐⭐⭐',
                    4 => '⭐⭐⭐⭐',
                    5 => '⭐⭐⭐⭐⭐',
                ]),

            Forms\Components\Select::make('itinerary_rating')
                ->options([
                    1 => '⭐',
                    2 => '⭐⭐',
                    3 => '⭐⭐⭐',
                    4 => '⭐⭐⭐⭐',
                    5 => '⭐⭐⭐⭐⭐',
                ]),

            Forms\Components\TextInput::make('overall_rating')
                ->numeric(),

            Forms\Components\Textarea::make('suggestion')
                ->rows(5)
                ->columnSpanFull(),
        ]);
    }

    // TABLE
    public static function table(Table $table): Table
    {
        return $table
           ->defaultSort('id', 'desc')

            ->columns([

                Tables\Columns\TextColumn::make('booking.booking_id')
    ->label('Booking No')
    ->sortable()
    ->searchable(),

Tables\Columns\TextColumn::make('infoGet.name')
    ->label('Member Name')
    ->sortable()
    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contact'),

                Tables\Columns\TextColumn::make('destination'),

                Tables\Columns\TextColumn::make('overall_rating')
                    ->label('Overall Rating'),

                Tables\Columns\TextColumn::make('suggestion')
                    ->limit(40),

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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedbacks::route('/'),
            'create' => Pages\CreateFeedbacks::route('/create'),
            'edit' => Pages\EditFeedbacks::route('/{record}/edit'),
        ];
    }
}	