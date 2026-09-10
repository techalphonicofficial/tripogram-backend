<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Offers & Coupons';
    protected static ?string $navigationLabel = 'Coupons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Details')
                    ->schema([

                        Forms\Components\TextInput::make('code')
                            ->label('Coupon Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\Select::make('discount_type')
                            ->label('Discount Type')
                            ->options([
                                'fixed' => 'Fixed (₹)',
                                
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('discount_value')
                            ->label('Discount Value')
                            ->numeric()
                            ->required()
                            ->minValue(0),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiry Date & Time')
                            ->required(),

                        Forms\Components\Toggle::make('is_used')
                            ->label('Mark as Used')
                            ->default(false),

                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Coupon Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\BadgeColumn::make('discount_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'fixed',
                        'warning' => 'percentage',
                    ]),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Value')
                    ->formatStateUsing(function ($record) {
                        if ($record->discount_type === 'fixed') {
                            return '₹' . number_format($record->discount_value, 2);
                        }
                        return $record->discount_value . '%';
                    }),

                Tables\Columns\BadgeColumn::make('is_used')
                    ->label('Used')
                    ->colors([
                        'success' => fn ($state) => $state == 1,
                        'gray' => fn ($state) => $state == 0,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Used' : 'Available'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime(),

                // ✅ Expiry Status
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        if ($record->is_used) {
                            return 'Used';
                        }

                        if ($record->expires_at && Carbon::now()->gt($record->expires_at)) {
                            return 'Expired';
                        }

                        return 'Active';
                    })
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Expired',
                        'warning' => 'Used',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),

            ])
           ->defaultSort('created_at', 'desc')	
            ->filters([
                Tables\Filters\SelectFilter::make('discount_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                    ]),

                Tables\Filters\SelectFilter::make('is_used')
                    ->label('Used Status')
                    ->options([
                        '1' => 'Used',
                        '0' => 'Available',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),

                // ✅ Quick Mark as Used
                Tables\Actions\Action::make('mark_used')
                    ->label('Mark Used')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->is_used)
                    ->action(function ($record) {
                        $record->update(['is_used' => 1]);
                    }),

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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}