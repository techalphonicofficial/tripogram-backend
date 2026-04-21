<?php

namespace App\Filament\Resources\PackagesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Toggle;

class ActiveCostsRelationManager extends RelationManager
{
    protected static string $relationship = 'activeCosts';
    protected static ?string $title = 'Active Costs';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('activity')
                    ->required(),

                TextInput::make('cost')
                    ->numeric()
                    ->required()
                    ->prefix('₹'),

                TextInput::make('discount_percent')
                    ->numeric()
                    ->label('Discount (%)')
                    ->required(),

                TextInput::make('gst_percent')
                    ->numeric()
                    ->label('GST (%)')
                    ->required(),
              Toggle::make('show_on_website')
    ->label('Show on Website')
    ->default(true),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('cost')->money('INR'),
                Tables\Columns\TextColumn::make('discount_percent')->label('Discount %'),
                Tables\Columns\TextColumn::make('gst_percent')->label('GST %'),
              Tables\Columns\IconColumn::make('show_on_website')
    ->label('Visible')
    ->boolean(),
                Tables\Columns\TextColumn::make('final_cost_excl')
                    ->label('Cost (Excl. GST)')
                    ->state(function ($record) {
                        $cost = (float) $record->cost;
                        $discount = (float) $record->discount_percent;
                        $afterDiscount = $cost - ($cost * $discount / 100);

                        return round($afterDiscount, 0);
                    })
                    ->money('INR'),

                Tables\Columns\TextColumn::make('final_cost_incl')
                    ->label('Cost (Incl. GST)')
                    ->state(function ($record) {
                        $cost = (float) $record->cost;
                        $discount = (float) $record->discount_percent;
                        $gst = (float) $record->gst_percent;

                        $afterDiscount = $cost - ($cost * $discount / 100);
                        $withGst = $afterDiscount + ($afterDiscount * $gst / 100);

                        return round($withGst, 0);
                    })
                    ->money('INR'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateStartingPrice();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateStartingPrice();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateStartingPrice();
                    }),
            ]);
    }

    private static function calculateTotal(Get $get): float
    {
        $cost = (float) $get('cost');
        $discount = (float) $get('discount_percent');
        $gst = (float) $get('gst_percent');

        if ($cost <= 0) {
            return 0;
        }

        $afterDiscount = $cost - ($cost * $discount / 100);
        $withGst = $afterDiscount + ($afterDiscount * $gst / 100);

        return round($withGst, 2);
    }

    protected function afterSave(Model $record): void
    {
        $this->updateStartingPrice();
    }

    protected function afterDelete(Model $record): void
    {
        $this->updateStartingPrice();
    }

    private function updateStartingPrice(): void
    {
        $package = $this->getOwnerRecord();

        if (! $package) {
            return;
        }

        // sabhi active costs fetch karo
        $minCost = $package->activeCosts()
            ->get()
            ->map(function ($row) {
                $cost = (float) $row->cost;
                $discount = (float) $row->discount_percent;
                return $cost - ($cost * $discount / 100);
            })
            ->min();

        // agar koi cost hi nahi bachi to 0 set karo
        $minCost = $minCost ?? 0;

        // ✅ Sirf packageDates update hoga, package table nahi
    
    }
}