<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingBookingResource\Pages;
use App\Models\Bookings;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PendingBookingResource extends Resource
{
    protected static ?string $model = Bookings::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Bookings';
    protected static ?string $navigationLabel = 'Pending Bookings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => 
                $query->where('status', 'pending')
            )

            ->columns([

                Tables\Columns\TextColumn::make('booking_id')
                    ->label('Booking ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y h:i A')
                    ->sortable(),

            ])

            ->actions([

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Bookings $record) {

                        $record->update([
                            'status' => 'confirmed',
                        ]);

                    }),

            ])

            ->bulkActions([

                Tables\Actions\BulkAction::make('confirm_selected')
                    ->label('Confirm Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {

                        foreach ($records as $record) {

                            $record->update([
                                'status' => 'confirmed',
                            ]);

                        }

                    }),

            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingBookings::route('/'),
        ];
    }
}