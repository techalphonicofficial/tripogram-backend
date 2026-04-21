<?php

namespace App\Filament\Resources\PackagesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Support\Carbon;
use CodeWithKyrian\FilamentDateRange\Forms\Components\DateRangePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Notifications\Notification;

class PackageDatesRelationManager extends RelationManager
{
    protected static string $relationship = 'packageDates';
    protected static ?string $title = 'Package Dates';

    /* ================= FORM ================= */
    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([

            Grid::make(2)->schema([

                /* ================= LEFT SIDE (GENERATOR) ================= */
                Grid::make(1)
                    ->visible(fn ($record) => $record === null)
                    ->schema([

                        DateRangePicker::make('event_period')
                            ->label('Booking Date Range')
                            ->minDate(now())
                            ->live()
                            ->dehydrated(false)
                            ->required(),

                        TextInput::make('package_duration')
                            ->label('Package Duration')
                            ->default(fn () => $this->getPackageDuration())
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-detected from package'),

                        TextInput::make('trip_length')
                            ->label('Trip Length (Days)')
                            ->default(fn () => $this->getTotalDaysFromDuration() . ' Days')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Checkbox::make('is_land_package')
                            ->label('🏕️ Is this a Land Package?')
                            ->helperText('Land Package: Same day start, no extra days | Regular Package: Extra return day')
                            ->live()
                            ->dehydrated(false)
                            ->default(fn () => $this->getOwnerRecord()?->is_land_package ?? false),

                        Select::make('week_day')
                            ->label('Trip Start Day (Weekly)')
                            ->options([
                                'Monday'    => 'Monday',
                                'Tuesday'   => 'Tuesday',
                                'Wednesday' => 'Wednesday',
                                'Thursday'  => 'Thursday',
                                'Friday'    => 'Friday',
                                'Saturday'  => 'Saturday',
                                'Sunday'    => 'Sunday',
                            ])
                            ->default('Saturday')
                            ->live()
                            ->dehydrated(false)
                            ->required()
                            ->helperText(fn (Get $get) => 
                                $get('is_land_package') 
                                    ? '✅ Land Package: Departure & Start on this day'
                                    : '🚐 Regular Package: Departure & Start on this day'
                            ),

                        Select::make('global_slots')
                            ->label('Available Slots')
                            ->multiple()
                            ->options([
                                'morning'   => '🌅 Morning',
                                'afternoon' => '☀️ Afternoon', 
                                'evening'   => '🌙 Evening',
                            ])
                            ->required()
                            ->live()
                            ->dehydrated(false),

                        Select::make('global_return_slot')
                            ->label('Default Return Slot')
                            ->options([
                                'morning'   => '🌅 Morning',
                                'afternoon' => '☀️ Afternoon',
                                'evening'   => '🌙 Evening',
                            ])
                            ->default('evening')
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->helperText('Har trip ke liye default return slot (slot2)'),

                        Select::make('global_status')
                            ->label('Default Status')
                            ->options([
                                'open'   => '✅ Open',
                                'close'  => '❌ Close',
                                'full'   => '⚠️ Full',
                                'fast-filling' => 'fast-filling',
                            ])
                            ->default('open')
                            ->live()
                            ->dehydrated(false),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generate')
                                ->label('Generate Trips')
                                ->color('primary')
                                ->icon('heroicon-o-calendar')
                                ->action(function (Get $get, Set $set) {
                                    $this->generateDates($get, $set);
                                })
                                ->requiresConfirmation()
                                ->disabled(fn (Get $get) => 
                                    !filled($get('event_period.start')) ||
                                    !filled($get('event_period.end')) ||
                                    !filled($get('week_day')) ||
                                    !filled($get('global_slots'))
                                ),
                        ]),
                    ]),

                /* ================= RIGHT SIDE (GENERATED DATES) ================= */
                Grid::make(1)
                    ->visible(fn (Get $get) => filled($get('days')) && count($get('days')) > 0)
                    ->schema([

                        Forms\Components\Section::make('📅 Generated Weekly Trips')
                            ->description(fn (Get $get) => 
                                'Har ' . ($get('week_day') ?? 'Saturday') . ' ko departure & start - ' .
                                ($get('is_land_package') ? '🏕️ LAND PACKAGE' : '🚐 REGULAR PACKAGE')
                            )
                            ->schema([

                                Repeater::make('days')
                                    ->label('')
                                    ->schema([

                                        Grid::make(5)->schema([
                                            TextInput::make('trip_number')
                                                ->label('Trip #')
                                                ->disabled()
                                                ->dehydrated(true),

                                            TextInput::make('departure_date')
                                                ->label('Departure Date')
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->extraAttributes(['class' => 'bg-gray-50']),

                                            TextInput::make('start_date')
                                                ->label('Start Date')
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            
                                            TextInput::make('end_date')
                                                ->label('End Date')
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->extraAttributes(['class' => 'bg-gray-50']),

                                            TextInput::make('day_name')
                                                ->label('Start Day')
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->extraAttributes(['class' => 'bg-gray-50']),

                                            TextInput::make('end_day_display')
                                                ->label('End Day')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextInput::make('duration_display')
                                                ->label('Duration')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('activity_dates')
                                                ->label('Activity Days')
                                                ->disabled()
                                                ->dehydrated(false),

                                            Select::make('slots')
                                                ->label('Available Slots')
                                                ->multiple()
                                                ->options([
                                                    'morning'   => '🌅 Morning',
                                                    'afternoon' => '☀️ Afternoon',
                                                    'evening'   => '🌙 Evening',
                                                ])
                                                ->required(),

                                            Select::make('status')
                                                ->label('Status')
                                                ->options([
                                                    'open'   => '✅ Open',
                                                    'close'  => '❌ Close',
                                                    'full'   => '⚠️ Full',
                                                    'fast-filling' => 'fast-filling',
                                                ])
                                                ->default(fn (Get $get) => $get('../../global_status') ?? 'open'),
                                        ]),

                                        Grid::make(3)->schema([
                                            Select::make('return_slot')
                                                ->label('Return Slot (slot2)')
                                                ->options([
                                                    'morning'   => '🌅 Morning (6 AM - 12 PM)',
                                                    'afternoon' => '☀️ Afternoon (12 PM - 5 PM)',
                                                    'evening'   => '🌙 Evening (5 PM - 9 PM)',
                                                ])
                                                ->default(fn (Get $get) => $get('../../global_return_slot') ?? 'evening')
                                                ->live()
                                                ->helperText('Return journey slot'),

                                            TextInput::make('increase_amount_by_percent')
                                                ->label('Increase %')
                                                ->numeric()
                                                ->default(0)
                                                ->suffix('%'),

                                            TextInput::make('decrease_amount_by_percent')
                                                ->label('Decrease %')
                                                ->numeric()
                                                ->default(0)
                                                ->suffix('%'),
                                        ]),

                                        TextInput::make('starting_price')
                                            ->label('Starting Price')
                                            ->numeric()
                                            ->disabled(fn () => ($this->getOwnerRecord()?->starting_price ?? 0) > 0)
                                            ->prefix('₹')
                                            ->required(),

                                        Forms\Components\Placeholder::make('return_slot_preview')
                                            ->label('Return Time Preview')
                                            ->content(fn (Get $get) => match ($get('return_slot')) {
                                                'morning' => '🚐 Return: 6 AM - 12 PM (Next Day Morning)',
                                                'afternoon' => '🚐 Return: 12 PM - 5 PM (Next Day Afternoon)',
                                                'evening' => '🚐 Return: 5 PM - 9 PM (Same Day Evening)',
                                                default => '🚐 Return timing not selected',
                                            })
                                            ->visible(fn (Get $get) => filled($get('return_slot'))),

                                        Forms\Components\Placeholder::make('end_date_warning')
                                            ->label('')
                                            ->content(fn (Get $get) => 
                                                $get('end_date') && Carbon::parse($get('end_date'))->gt(Carbon::parse($get('../../event_period.end'))) 
                                                    ? '⚠️ Note: Trip end date range se bahar hai' 
                                                    : ''
                                            )
                                            ->visible(fn (Get $get) => 
                                                $get('end_date') && Carbon::parse($get('end_date'))->gt(Carbon::parse($get('../../event_period.end')))
                                            ),

                                        Hidden::make('nights'),
                                        Hidden::make('days_count'),
                                        Hidden::make('arrival_date'),
                                        Hidden::make('return_date'),
                                        Hidden::make('start_day'),
                                        Hidden::make('end_day'),
                                        Hidden::make('is_land_package'),
                                    ])
                                    ->dehydrated(true)
                                    ->columns(1)
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->cloneable()
                                    ->itemLabel(fn (array $state): ?string => 
                                        isset($state['departure_date']) 
                                            ? 'Trip ' . ($state['trip_number'] ?? '') . ': ' . $state['departure_date'] . ' → ' . $state['end_date']
                                            : 'New Trip'
                                    ),
                            ]),
                    ]),

                /* ================= EDIT MODE ================= */
                Grid::make(1)
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Forms\Components\Section::make('✏️ Edit Package Date')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('departure_date')
                                        ->label('Departure Date')
                                        ->required(),
                                    TextInput::make('start_date')
                                        ->label('Start Date')
                                        ->required(),
                                    TextInput::make('end_date')
                                        ->label('End Date')
                                        ->required(),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('day_name')
                                        ->label('Start Day')
                                        ->required(),
                                    TextInput::make('end_day')
                                        ->label('End Day')
                                        ->required(),
                                ]),

                                Grid::make(2)->schema([
                                    Select::make('slots')
                                        ->label('Slots')
                                        ->multiple()
                                        ->options([
                                            'morning'   => '🌅 Morning',
                                            'afternoon' => '☀️ Afternoon',
                                            'evening'   => '🌙 Evening',
                                        ])
                                        ->required(),

                                    Select::make('slot2')
                                        ->label('Return Slot (slot2)')
                                        ->options([
                                            'morning'   => '🌅 Morning (6 AM - 12 PM)',
                                            'afternoon' => '☀️ Afternoon (12 PM - 5 PM)',
                                            'evening'   => '🌙 Evening (5 PM - 9 PM)',
                                        ])
                                        ->default('evening')
                                        ->required()
                                        ->helperText('Return journey timing'),
                                ]),

                                Grid::make(2)->schema([
                                    Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'open'   => 'Open',
                                            'close'  => 'Close',
                                            'full'   => 'Full',
                                            'hot'    => 'Hot',
                                            'fast-filling' => 'fast-filling',
                                        ])
                                        ->required(),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('increase_amount_by_percent')
                                        ->label('Increase %')
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('%'),

                                    TextInput::make('decrease_amount_by_percent')
                                        ->label('Decrease %')
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('%'),
                                ]),

                                Forms\Components\Placeholder::make('edit_return_preview')
                                    ->label('Return Time Preview')
                                    ->content(fn (Get $get) => match ($get('slot2')) {
                                        'morning' => '🚐 Return: 6 AM - 12 PM (Next Day Morning)',
                                        'afternoon' => '🚐 Return: 12 PM - 5 PM (Next Day Afternoon)',
                                        'evening' => '🚐 Return: 5 PM - 9 PM (Same Day Evening)',
                                        default => '🚐 Return timing not selected',
                                    }),

                                Hidden::make('special')->default(false),
                            ]),
                    ]),
            ]),
        ]);
    }

    /* ================= TABLE ================= */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                if (!request()->has('tableSearch') && !request()->has('tableFilters')) {
                    $query->where('status', 'open');
                }
            })
            ->columns([
                TextColumn::make('departure_date')
                    ->date('d M, Y')
                    ->label('Departure')
                    ->sortable(),
                    
                TextColumn::make('start_date')
                    ->date('d M, Y')
                    ->label('Start')
                    ->sortable(),
                    
                TextColumn::make('end_date')
                    ->date('d M, Y')
                    ->label('End')
                    ->sortable(),
                    
                TextColumn::make('day_name')
                    ->label('Start Day')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                    
                TextColumn::make('end_day')
                    ->label('End Day')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => 
                        $state ?? Carbon::parse($record->end_date)->format('l')
                    ),
                    
                TextColumn::make('slots')
                    ->label('Slots')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function ($state) {
                        if (is_string($state)) {
                            $state = json_decode($state, true);
                        }
                        $slots = is_array($state) ? $state : [];
                        $icons = [
                            'morning' => '🌅',
                            'afternoon' => '☀️',
                            'evening' => '🌙',
                        ];
                        return collect($slots)->map(fn($slot) => 
                            ($icons[$slot] ?? '') . ' ' . ucfirst($slot)
                        )->implode(', ');
                    }),

                TextColumn::make('slot2')
                    ->label('Return Slot')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'morning' => '🌅 Morning',
                        'afternoon' => '☀️ Afternoon',
                        'evening' => '🌙 Evening',
                        default => $state,
                    })
                    ->tooltip(fn ($state) => match ($state) {
                        'morning' => 'Return timing: 6 AM - 12 PM (Next Day)',
                        'afternoon' => 'Return timing: 12 PM - 5 PM (Next Day)',
                        'evening' => 'Return timing: 5 PM - 9 PM (Same Day)',
                        default => null,
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'close' => 'danger',
                        'full' => 'warning',
                        'hot' => 'info',
                        'fast-filling' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('increase_amount_by_percent')
                    ->label('+%')
                    ->color('danger')
                    ->suffix('%')
                    ->toggleable(),

                TextColumn::make('decrease_amount_by_percent')
                    ->label('-%')
                    ->color('success')
                    ->suffix('%')
                    ->toggleable(),
            ])
            ->defaultSort('departure_date', 'asc')
            
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'open' => '✅ Open',
                        'close' => '❌ Close',
                        'full' => '⚠️ Full',
                        'hot' => '🔥 Hot',
                        'fast-filling' => '⚡ Fast Filling',
                    ])
                    ->placeholder('All Statuses'),
            ])
            
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Delete Selected Trips')
                    ->modalDescription('Are you sure you want to delete these trips? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Delete')
                    ->action(function ($records) {
                        $count = $records->count();
                        foreach ($records as $record) {
                            $record->delete();
                        }
                        Notification::make()
                            ->success()
                            ->title($count . ' trips deleted successfully')
                            ->send();
                    }),
                    
                Tables\Actions\BulkAction::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label('New Status')
                            ->options([
                                'open'   => '✅ Open',
                                'close'  => '❌ Close',
                                'full'   => '⚠️ Full',
                                'hot'    => '🔥 Hot',
                                'fast-filling' => '⚡ Fast Filling',
                            ])
                            ->required()
                            ->default('open'),
                    ])
                    ->action(function ($records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
                            $record->update(['status' => $data['status']]);
                            $count++;
                        }
                        Notification::make()
                            ->success()
                            ->title($count . ' trips updated')
                            ->body('Status updated to: ' . $data['status'])
                            ->send();
                    }),
                    
                Tables\Actions\BulkAction::make('updateSlots')
                    ->label('Update Slots')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->form([
                        Select::make('slots')
                            ->label('Available Slots')
                            ->multiple()
                            ->options([
                                'morning'   => '🌅 Morning',
                                'afternoon' => '☀️ Afternoon',
                                'evening'   => '🌙 Evening',
                            ])
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
                            $record->update(['slots' => json_encode($data['slots'])]);
                            $count++;
                        }
                        Notification::make()
                            ->success()
                            ->title($count . ' trips updated')
                            ->body('Slots updated successfully.')
                            ->send();
                    }),
                    
                Tables\Actions\BulkAction::make('updateReturnSlot')
                    ->label('Update Return Slot')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        Select::make('slot2')
                            ->label('Return Slot')
                            ->options([
                                'morning'   => '🌅 Morning (6 AM - 12 PM)',
                                'afternoon' => '☀️ Afternoon (12 PM - 5 PM)',
                                'evening'   => '🌙 Evening (5 PM - 9 PM)',
                            ])
                            ->required()
                            ->default('evening'),
                    ])
                    ->action(function ($records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
                            $record->update(['slot2' => $data['slot2']]);
                            $count++;
                        }
                        Notification::make()
                            ->success()
                            ->title($count . ' trips updated')
                            ->body('Return slot updated to: ' . $data['slot2'])
                            ->send();
                    }),
            ])
            
            ->checkIfRecordIsSelectableUsing(fn ($record): bool => true)
            ->selectCurrentPageOnly(false)
            
            ->headerActions([
                CreateAction::make()
                    ->label('Generate Weekly Trips')
                    ->modalHeading('Generate Weekly Package Dates')
                    ->modalWidth('7xl')
                    ->using(function (array $data, RelationManager $livewire) {
                        $ownerRecord = $livewire->getOwnerRecord();
                        if (!$ownerRecord || empty($data['days'])) {
                            return null;
                        }
                        $created = [];
                        foreach ($data['days'] as $day) {
                            $endDay = $day['end_day'] ?? Carbon::parse($day['end_date'])->format('l');
                            $created[] = $ownerRecord->packageDates()->create([
                                'departure_date' => $day['departure_date'] ?? null,
                                'start_date' => $day['start_date'] ?? null,
                                'end_date'   => $day['end_date'] ?? null,
                                'day_name'   => $day['day_name'] ?? null,
                                'end_day'    => $endDay,
                                'slots'      => json_encode($day['slots'] ?? []),
                                'slot2'      => $day['return_slot'] ?? 'evening',
                                'status'     => $day['status'] ?? 'open',
                                'starting_price' => $day['starting_price'] ?? 0,
                                'increase_amount_by_percent' => $day['increase_amount_by_percent'] ?? 0,
                                'decrease_amount_by_percent' => $day['decrease_amount_by_percent'] ?? 0,
                                'special'    => false,
                            ]);
                        }
                        Notification::make()
                            ->success()
                            ->title(count($created) . ' weekly trips created successfully')
                            ->send();
                        return $created[0] ?? null;
                    }),
            ])
            
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Delete Trip')
                    ->modalDescription('Are you sure you want to delete this trip?')
                    ->action(function ($record) {
                        $record->delete();
                        Notification::make()
                            ->success()
                            ->title('Trip deleted successfully')
                            ->send();
                    }),
            ]);
    }
    
    private function getPackageDuration(): string
    {
        $ownerRecord = $this->getOwnerRecord();
        return $ownerRecord?->duration ?? '2N-3D';
    }

    private function getTotalDaysFromDuration(): int
    {
        $duration = $this->getPackageDuration();
        if (preg_match('/(\d+)\s*D/i', $duration, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/(\d+)\s*N/i', $duration, $matches)) {
            return (int) $matches[1] + 1;
        }
        return 3;
    }

    private function getNightsFromDuration(): int
    {
        $duration = $this->getPackageDuration();
        if (preg_match('/(\d+)\s*N/i', $duration, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/(\d+)\s*D/i', $duration, $matches)) {
            return (int) $matches[1] - 1;
        }
        return 2;
    }

    private function generateDates(Get $get, Set $set): void
    {
        if (
            !filled($get('event_period.start')) ||
            !filled($get('event_period.end')) ||
            !filled($get('week_day')) ||
            !filled($get('global_slots'))
        ) {
            return;
        }

        $startRange = Carbon::parse($get('event_period.start'));
        $endRange   = Carbon::parse($get('event_period.end'));

        $weekday   = $get('week_day');
        $slots     = $get('global_slots');
        $returnSlot = $get('global_return_slot') ?? 'evening';
        $defaultStatus = $get('global_status') ?? 'open';
        $isLandPackage = $get('is_land_package') ?? false;
        
        $nights = $this->getNightsFromDuration();
        $totalDays = $this->getTotalDaysFromDuration();

        $generatedDays = [];
        $current = $startRange->copy();
        $tripCounter = 1;

        $startingPrice = $this->getOwnerRecord()?->starting_price ?? 0;

        // Find first occurrence of selected weekday
        while ($current->format('l') !== $weekday && $current->lte($endRange)) {
            $current->addDay();
        }

        // Generate trips
        while ($current->lte($endRange)) {
            
            // Departure and Start date are ALWAYS the selected day
            $departureDate = $current->copy();
            $startDate = $current->copy();
            
            if ($isLandPackage) {
                // LAND PACKAGE: End date = Start date + (Total Days - 1)
                // Example 5N-6D: 6 July to 11 July (6 days total)
                $endDate = $current->copy()->addDays($totalDays - 1);
            } else {
                // REGULAR PACKAGE: End date = Start date + Total Days
                // Example 5N-6D: 6 July to 12 July (7 days total with return)
                $endDate = $current->copy()->addDays($totalDays+1);
            }
            
            $startDay = $startDate->format('l');
            $endDay = $endDate->format('l');
            
            $durationDisplay = $nights . 'N/' . $totalDays . 'D';
            if ($isLandPackage) {
                $durationDisplay .= ' (LAND PACKAGE)';
            } else {
                $durationDisplay .= ' (REGULAR PACKAGE)';
            }
            
            $endDateWarning = '';
            if ($endDate->gt($endRange)) {
                $endDateWarning = ' (Ends after range)';
            }
            
            $generatedDays[] = [
                'trip_number'     => 'Trip #' . $tripCounter,
                'departure_date'  => $departureDate->format('Y-m-d'),
                'start_date'      => $startDate->format('Y-m-d'),
                'end_date'        => $endDate->format('Y-m-d'),
                'day_name'        => $startDay,
                'end_day'         => $endDay,
                'end_day_display' => $endDay,
                'duration_display' => $durationDisplay . $endDateWarning,
                'activity_dates'   => $startDate->format('d M') . ' to ' . $endDate->format('d M'),
                'nights'           => $nights,
                'days_count'       => $totalDays,
                'arrival_date'     => $startDate->format('Y-m-d'),
                'return_date'      => $endDate->format('Y-m-d'),
                'start_day'        => $startDay,
                'end_day_hidden'   => $endDay,
                'slots'            => $slots,
                'return_slot'      => $returnSlot,
                'status'           => $defaultStatus,
                'starting_price'   => $startingPrice,
                'increase_amount_by_percent' => 0,
                'decrease_amount_by_percent' => 0,
                'is_land_package'  => $isLandPackage,
            ];

            $current->addWeek();
            $tripCounter++;
        }

        if (count($generatedDays) > 0) {
            $set('days', $generatedDays);
            $packageType = $isLandPackage ? '🏕️ Land Package' : '🚐 Regular Package';
            Notification::make()
                ->success()
                ->title(count($generatedDays) . ' weekly trips generated')
                ->body($packageType . ' - ' . $nights . 'N/' . $totalDays . 'D on ' . $weekday)
                ->send();
        } else {
            $set('days', []);
            Notification::make()
                ->warning()
                ->title('No trips generated')
                ->body('Range mein koi ' . $weekday . ' nahi mila')
                ->send();
        }
    }
}