<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingsResource\Pages;
use App\Models\Bookings;
use App\Models\InfoGet;
use App\Models\Packages;
use App\Models\PackageDates;
use App\Models\ActiveCosts;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Carbon\Carbon;

class BookingsResource extends Resource
{
    protected static ?string $model = Bookings::class;

    protected static ?string $navigationGroup = 'Travel Management';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) Bookings::count();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

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
        
        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        if (!in_array('bookings', $permissionResources)) {
            return false;
        }

        return true;
    }

    /**
     * Apply percentage adjustment to an amount
     */
    protected static function applyPercentageAdjustment(float $amount, float $increasePercent, float $decreasePercent): float
    {
        $afterIncrease = $amount + ($amount * $increasePercent / 100);
        $finalAmount = $afterIncrease - ($afterIncrease * $decreasePercent / 100);
        return $finalAmount;
    }

    /**
     * Calculate total for a single activity
     */
    protected static function calculateActivityTotal(callable $set, callable $get): void
    {
        $cost = (float)$get('cost');
        $discountAmount = (float)$get('discount_amount');
        $gstPercent = (float)$get('gst_percent');
        $quantity = (float)$get('quantity');
        
        $totalWithDiscount = ($cost - $discountAmount) * $quantity;
        $totalWithDiscountAndGst = $totalWithDiscount + ($totalWithDiscount * $gstPercent / 100);
        
        $set('total_with_discount', $totalWithDiscount);
        $set('total_with_discount_and_gst', $totalWithDiscountAndGst);
    }

    /**
     * Calculate total amount including base price and all activities
     */
    protected static function calculateTotalAmount(callable $set, callable $get): void
    {
        $basePrice = (float)($get('base_price') ?? 0);
        
        $activitiesTotal = 0;
        $activities = $get('active_cost') ?? [];
        
        foreach ($activities as $activity) {
            if (isset($activity['total_with_discount_and_gst'])) {
                $activitiesTotal += (float)$activity['total_with_discount_and_gst'];
            }
        }
        
        $finalAmount = $activitiesTotal;
        $set('final_amount', $finalAmount);
        
        $paymentMode = $get('payment_mode');
        if ($paymentMode === 'online') {
            $transactions = $get('payment_transactions') ?? [];
            $totalPaid = 0;
            foreach ($transactions as $transaction) {
                $totalPaid += (float)($transaction['amount'] ?? 0);
            }
            $set('paid_amount', $totalPaid);
            $set('due_amount', $finalAmount - $totalPaid);
        } else {
            $paidAmount = (float)($get('paid_amount') ?? 0);
            $set('due_amount', $finalAmount - $paidAmount);
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\Section::make('Customer Info')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('full_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(10),
                            ]),

                        Forms\Components\Section::make('Package Info')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('package_id')
                                    ->label('Package')
                                    ->options(function () {
                                        return Packages::pluck('title', 'id')->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $package = Packages::find($state);
                                        if ($package) {
                                            $set('package_title', $package->title);
                                            $set('duration', $package->duration);
                                            $set('pickup', $package->pickup ?? '');
                                            $set('drop', $package->drop ?? '');
                                            
                                            $set('start_date', null);
                                            $set('end_date', null);
                                            $set('package_date_id', null);
                                            $set('base_price', null);
                                            $set('increase_percent', 0);
                                            $set('decrease_percent', 0);
                                            
                                            $set('active_cost', []);
                                        }
                                    }),
                                Forms\Components\TextInput::make('package_title')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('duration')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('pickup')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('drop')
                                    ->disabled()
                                    ->dehydrated(true),
                                
                                Forms\Components\Select::make('start_date')
                                    ->label('Select Package Start Date')
                                    ->options(function (callable $get) {
                                        $packageId = $get('package_id');
                                        if (!$packageId) {
                                            return [];
                                        }
                                        
                                        $packageDates = PackageDates::where('package_id', $packageId)
                                            ->where('start_date', '>=', Carbon::now())
                                            ->orderBy('start_date', 'asc')
                                            ->get();
                                        
                                        $options = [];
                                        foreach ($packageDates as $packageDate) {
                                            $startDate = Carbon::parse($packageDate->start_date);
                                            $endDate = Carbon::parse($packageDate->end_date);
                                            $increasePercent = $packageDate->increase_amount_by_percent ?? 0;
                                            $decreasePercent = $packageDate->decrease_amount_by_percent ?? 0;
                                            
                                            $badge = '';
                                            if ($increasePercent > 0) {
                                                $badge = " [+{$increasePercent}%]";
                                            }
                                            if ($decreasePercent > 0) {
                                                $badge = " [-{$decreasePercent}%]";
                                            }
                                            
                                            // FIX: Store actual start_date value
                                            $options[$packageDate->start_date] = $startDate->format('d M, Y') . ' to ' . $endDate->format('d M, Y') . $badge;
                                        }
                                        
                                        return $options;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            // FIX: Find by start_date
                                            $packageDate = PackageDates::where('package_id', $get('package_id'))
                                                ->where('start_date', $state)
                                                ->first();
                                            
                                            if ($packageDate) {
                                                $set('end_date', $packageDate->end_date);
                                                $set('package_date_id', $packageDate->id);
                                                
                                                $increasePercent = (float)($packageDate->increase_amount_by_percent ?? 0);
                                                $decreasePercent = (float)($packageDate->decrease_amount_by_percent ?? 0);
                                                
                                                $set('increase_percent', $increasePercent);
                                                $set('decrease_percent', $decreasePercent);
                                                
                                                $basePrice = (float)($packageDate->starting_price ?? 0);
                                                $finalBasePrice = static::applyPercentageAdjustment($basePrice, $increasePercent, $decreasePercent);
                                                
                                                $set('base_price', $finalBasePrice);
                                                
                                                $activities = $get('active_cost') ?? [];
                                                $updatedActivities = [];
                                                
                                                foreach ($activities as $index => $activity) {
                                                    if (isset($activity['activity']) && $activity['activity']) {
                                                        $originalCost = (float)($activity['original_cost'] ?? $activity['cost'] ?? 0);
                                                        if ($originalCost > 0) {
                                                            $adjustedCost = static::applyPercentageAdjustment($originalCost, $increasePercent, $decreasePercent);
                                                            $updatedActivities[$index]['id'] = $activity['id'] ?? null;
                                                            $updatedActivities[$index]['cost'] = $adjustedCost;
                                                            $updatedActivities[$index]['original_cost'] = $originalCost;
                                                            $updatedActivities[$index]['activity'] = $activity['activity'];
                                                            $updatedActivities[$index]['discount_amount'] = $activity['discount_amount'] ?? 0;
                                                            $updatedActivities[$index]['gst_percent'] = $activity['gst_percent'] ?? 0;
                                                            $updatedActivities[$index]['quantity'] = $activity['quantity'] ?? 1;
                                                            $updatedActivities[$index]['package_id'] = $activity['package_id'] ?? $get('package_id');
                                                            
                                                            $discountAmount = (float)($activity['discount_amount'] ?? 0);
                                                            $gstPercent = (float)($activity['gst_percent'] ?? 0);
                                                            $quantity = (float)($activity['quantity'] ?? 1);
                                                            
                                                            $totalWithDiscount = ($adjustedCost - $discountAmount) * $quantity;
                                                            $totalWithDiscountAndGst = $totalWithDiscount + ($totalWithDiscount * $gstPercent / 100);
                                                            
                                                            $updatedActivities[$index]['total_with_discount'] = $totalWithDiscount;
                                                            $updatedActivities[$index]['total_with_discount_and_gst'] = $totalWithDiscountAndGst;
                                                        }
                                                    }
                                                }
                                                
                                                if (!empty($updatedActivities)) {
                                                    $set('active_cost', $updatedActivities);
                                                }
                                                
                                                static::calculateTotalAmount($set, $get);
                                            }
                                        }
                                    })
                                    ->default(function ($record) {
                                        if ($record && $record->start_date) {
                                            return $record->start_date;
                                        }
                                        return null;
                                    }),
                                
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->disabled()
                                    ->dehydrated(true),
                                
                                Forms\Components\Hidden::make('package_date_id')
                                    ->default(function ($record) {
                                        if ($record && $record->package_date_id) {
                                            return $record->package_date_id;
                                        }
                                        return null;
                                    }),
                                
                                Forms\Components\Hidden::make('increase_percent')
                                    ->default(function ($record) {
                                        if ($record && $record->increase_percent) {
                                            return $record->increase_percent;
                                        }
                                        return 0;
                                    }),
                                
                                Forms\Components\Hidden::make('decrease_percent')
                                    ->default(function ($record) {
                                        if ($record && $record->decrease_percent) {
                                            return $record->decrease_percent;
                                        }
                                        return 0;
                                    }),
                                
                                Forms\Components\TextInput::make('base_price')
                                    ->label('Base Package Price')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->prefix('₹')
                                    ->default(function ($record) {
                                        if ($record && $record->base_price) {
                                            return $record->base_price;
                                        }
                                        return 0;
                                    }),
                            ]),

                        Forms\Components\Section::make('Costs')
                            ->schema([
                                Forms\Components\Repeater::make('active_cost')
                                    ->label('Activities')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\Hidden::make('package_id'),
                                        Forms\Components\Hidden::make('original_cost')
                                            ->default(0),
                                        
                                        Forms\Components\Select::make('activity')
                                            ->label('Activity')
                                            ->required()
                                            ->searchable()
                                            ->reactive()
                                            ->options(function (callable $get) {
                                                $packageId = $get('../../package_id');
                                                if (!$packageId) {
                                                    return [];
                                                }
                                                
                                                return ActiveCosts::where('package_id', $packageId)
                                                    ->where('status', 1)
                                                    ->pluck('activity', 'activity')
                                                    ->toArray();
                                            })
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('activity')
                                                    ->label('Activity Name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('cost')
                                                    ->label('Original Cost')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                                Forms\Components\TextInput::make('gst_percent')
                                                    ->label('GST Percentage')
                                                    ->numeric()
                                                    ->default(0),
                                                Forms\Components\TextInput::make('discount_amount')
                                                    ->label('Discount Amount')
                                                    ->numeric()
                                                    ->default(0),
                                            ])
                                            ->createOptionUsing(function (array $data, callable $get) {
                                                $packageId = $get('../../package_id');
                                                
                                                $activeCost = ActiveCosts::create([
                                                    'package_id' => $packageId,
                                                    'activity' => $data['activity'],
                                                    'cost' => $data['cost'] ?? 0,
                                                    'gst_percent' => $data['gst_percent'] ?? 0,
                                                    'discount_amount' => $data['discount_amount'] ?? 0,
                                                    'status' => 1,
                                                ]);
                                                
                                                return $activeCost->activity;
                                            })
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state) {
                                                    $packageId = $get('../../package_id');
                                                    if (!$packageId) {
                                                        return;
                                                    }
                                                    
                                                    $activityData = ActiveCosts::where('package_id', $packageId)
                                                        ->where('activity', $state)
                                                        ->where('status', 1)
                                                        ->first();
                                                    
                                                    if ($activityData) {
                                                        $originalCost = (float)$activityData->cost;
                                                        $gstPercent = (float)($activityData->gst_percent ?? 0);
                                                        $discountAmount = (float)($activityData->discount_amount ?? 0);
                                                        
                                                        $increasePercent = (float)($get('../../increase_percent') ?? 0);
                                                        $decreasePercent = (float)($get('../../decrease_percent') ?? 0);
                                                        
                                                        $adjustedCost = static::applyPercentageAdjustment($originalCost, $increasePercent, $decreasePercent);
                                                        
                                                        $set('original_cost', $originalCost);
                                                        $set('cost', $adjustedCost);
                                                        $set('gst_percent', $gstPercent);
                                                        $set('discount_amount', $discountAmount);
                                                        $set('quantity', 1);
                                                        $set('package_id', $packageId);
                                                        
                                                        static::calculateActivityTotal($set, $get);
                                                        static::calculateTotalAmount($set, $get);
                                                    }
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('cost')
                                            ->label('Adjusted Cost')
                                            ->numeric()
                                            ->required()
                                            ->dehydrated(true)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                static::calculateActivityTotal($set, $get);
                                                static::calculateTotalAmount($set, $get);
                                            })
                                            ->helperText(function (callable $get) {
                                                $original = (float)($get('original_cost') ?? 0);
                                                $increase = (float)($get('../../increase_percent') ?? 0);
                                                $decrease = (float)($get('../../decrease_percent') ?? 0);
                                                
                                                if ($original > 0 && ($increase > 0 || $decrease > 0)) {
                                                    $increaseText = $increase > 0 ? "+{$increase}%" : '';
                                                    $decreaseText = $decrease > 0 ? "-{$decrease}%" : '';
                                                    $separator = $increase > 0 && $decrease > 0 ? ' then ' : '';
                                                    return "Original: ₹{$original} ({$increaseText}{$separator}{$decreaseText} applied)";
                                                }
                                                return '';
                                            }),
                                        
                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label('Discount Amount')
                                            ->numeric()
                                            ->default(0)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                static::calculateActivityTotal($set, $get);
                                                static::calculateTotalAmount($set, $get);
                                            }),
                                        
                                        Forms\Components\TextInput::make('gst_percent')
                                            ->label('GST Percentage')
                                            ->numeric()
                                            ->default(0)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                static::calculateActivityTotal($set, $get);
                                                static::calculateTotalAmount($set, $get);
                                            }),
                                        
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                static::calculateActivityTotal($set, $get);
                                                static::calculateTotalAmount($set, $get);
                                            }),
                                        
                                        Forms\Components\TextInput::make('total_with_discount')
                                            ->label('Total after Discount')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(true),
                                        
                                        Forms\Components\TextInput::make('total_with_discount_and_gst')
                                            ->label('Total after Discount + GST')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(true),
                                    ])
                                    ->columns(3)
                                    ->collapsed(false)
                                    ->collapsible(false)
                                    ->default([])
                                    ->createItemButtonLabel('Add Activity')
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, callable $get): array {
                                        unset($data['original_cost']);
                                        
                                        if (!isset($data['package_id']) || empty($data['package_id'])) {
                                            $data['package_id'] = $get('../../package_id');
                                        }
                                        
                                        return $data;
                                    })
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        static::calculateTotalAmount($set, $get);
                                    }),
                            ]),

                        Forms\Components\Section::make('Payment Info')
                            ->schema([
                                Forms\Components\Select::make('payment_mode')
                                    ->options([
                                        'cash' => 'Cash',
                                        'online' => 'Online',
                                        'other' => 'Other',
                                    ])
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state !== 'online') {
                                            $set('payment_transactions', []);
                                        }
                                        $set('payment_type', null);
                                        static::calculateTotalAmount($set, $get);
                                    }),
                                
                                Forms\Components\Repeater::make('payment_transactions')
                                    ->label('Payment Transactions')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('utr_number')
                                                    ->label('UTR Number / Transaction ID')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Enter UTR number or transaction ID'),
                                                
                                                Forms\Components\DatePicker::make('transaction_date')
                                                    ->label('Transaction Date')
                                                    ->required()
                                                    ->default(now()),
                                                
                                                Forms\Components\TextInput::make('amount')
                                                    ->label('Transaction Amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->placeholder('Enter amount'),
                                                
                                                Forms\Components\Select::make('payment_type')
                                                    ->label('Payment Type')
                                                    ->options([
                                                        'full' => 'Full Payment',
                                                        'half' => 'Half Payment',
                                                        'partial' => 'Partial Payment',
                                                    ])
                                                    ->required()
                                                    ->default('partial'),
                                            ]),
                                        
                                        Forms\Components\FileUpload::make('screenshot')
                                            ->label('Payment Screenshot')
                                            ->image()
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->imageResizeTargetWidth('800')
                                            ->imageResizeTargetHeight('800')
                                            ->imageResizeMode('cover')
                                            ->directory('payment-screenshots')
                                            ->visibility('public')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                                            ->maxSize(2048)
                                            ->helperText('Upload payment screenshot (Max 5 images, 2MB each)')
                                            ->required(),
                                        
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Additional Notes')
                                            ->maxLength(500)
                                            ->rows(2)
                                            ->placeholder('Any additional information about this transaction'),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->default([])
                                    ->visible(fn (callable $get) => $get('payment_mode') === 'online')
                                    ->required(fn (callable $get) => $get('payment_mode') === 'online')
                                    ->minItems(1)
                                    ->maxItems(10)
                                    ->itemLabel(fn (array $state): ?string => $state['utr_number'] ?? 'New Transaction')
                                    ->createItemButtonLabel('Add Another Transaction')
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        static::calculateTotalAmount($set, $get);
                                    }),
                                
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('payment_type')
                                            ->label('Payment Type')
                                            ->options([
                                                'full' => 'Full Payment',
                                                'half' => 'Half Payment',
                                            ])
                                            ->required()
                                            ->default('full'),
                                        Forms\Components\TextInput::make('paid_amount')
                                            ->label('Amount Paid')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->default(0)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                static::calculateTotalAmount($set, $get);
                                            }),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (callable $get) => $get('payment_mode') !== 'online'),
                                
                                Forms\Components\Hidden::make('payment_type')
                                    ->default(null),
                                
                                Forms\Components\TextInput::make('final_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->disabled()
                                    ->dehydrated(true),
                                
                                Forms\Components\TextInput::make('paid_amount')
                                    ->numeric()
                                    ->disabled(fn (callable $get) => $get('payment_mode') === 'online')
                                    ->dehydrated(true)
                                    ->default(0),
                                
                                Forms\Components\TextInput::make('due_amount')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(0),
                                
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('booking_id')->searchable(),
                Tables\Columns\TextColumn::make('full_name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('package_title')->searchable(),
                Tables\Columns\TextColumn::make('gender_count')
                    ->label('Male / Female / Other Count')
                    ->getStateUsing(function ($record) {
                        $data = $record->data_get;
                        if (is_string($data)) {
                            $data = json_decode($data, true);
                        }
                        if (!is_array($data)) {
                            return 'Male: 0 | Female: 0 | Other: 0';
                        }
                        $male = 0;
                        $female = 0;
                        $other = 0;
                        foreach ($data as $sharingType) {
                            if (!empty($sharingType['members'])) {
                                foreach ($sharingType['members'] as $member) {
                                    $gender = strtolower($member['gender'] ?? '');
                                    if ($gender === 'male') {
                                        $male++;
                                    } elseif ($gender === 'female') {
                                        $female++;
                                    } elseif ($gender === 'other') {
                                        $other++;
                                    }
                                }
                            }
                        }
                        return "Male: {$male} | Female: {$female} | Other: {$other}";
                    }),
                Tables\Columns\TextColumn::make('booking_token')
                    ->label('Booking')
                    ->url(fn ($record) => 'https://www.enlivetrips.com/booking-detail?id=' . $record->booking_token)
                    ->openUrlInNewTab()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('utr_numbers')
                    ->label('UTR Numbers')
                    ->getStateUsing(function ($record) {
                        $transactions = $record->payment_transactions;
                        if (empty($transactions)) {
                            return '-';
                        }
                        if (is_string($transactions)) {
                            $transactions = json_decode($transactions, true);
                        }
                        if (!is_array($transactions)) {
                            return '-';
                        }
                        $utrs = array_column($transactions, 'utr_number');
                        return implode(', ', $utrs);
                    })
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $transactions = $record->payment_transactions;
                        if (is_string($transactions)) {
                            $transactions = json_decode($transactions, true);
                        }
                        if (is_array($transactions)) {
                            $details = [];
                            foreach ($transactions as $trans) {
                                $details[] = "{$trans['utr_number']} - ₹{$trans['amount']} - {$trans['transaction_date']}";
                            }
                            return implode("\n", $details);
                        }
                        return null;
                    }),
                
                Tables\Columns\ImageColumn::make('payment_screenshots')
                    ->label('Screenshots')
                    ->getStateUsing(function ($record) {
                        $transactions = $record->payment_transactions;
                        if (empty($transactions)) {
                            return [];
                        }
                        if (is_string($transactions)) {
                            $transactions = json_decode($transactions, true);
                        }
                        if (!is_array($transactions)) {
                            return [];
                        }
                        $screenshots = [];
                        foreach ($transactions as $transaction) {
                            if (!empty($transaction['screenshot'])) {
                                $screenshots = array_merge($screenshots, (array)$transaction['screenshot']);
                            }
                        }
                        return $screenshots;
                    })
                    ->circular(false)
                    ->width(50)
                    ->height(50)
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
                
                Tables\Columns\TagsColumn::make('active_cost')
                    ->label('Activities')
                    ->getStateUsing(fn($record) => collect($record->active_cost)->pluck('activity')->toArray()),
                Tables\Columns\TextColumn::make('base_price')->money('INR', true),
                Tables\Columns\TextColumn::make('final_amount')->money('INR', true),
                Tables\Columns\TextColumn::make('paid_amount')->money('INR', true),
                Tables\Columns\TextColumn::make('due_amount')->money('INR', true),
                Tables\Columns\BadgeColumn::make('payment_mode')
                    ->colors([
                        'success' => 'cash',
                        'warning' => 'online',
                        'gray' => 'other',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_type')
                    ->colors([
                        'success' => 'full',
                        'warning' => 'half',
                        'info' => 'partial',
                    ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'pending',
                    'success' => 'confirmed',
                    'danger' => 'cancelled',
                ]),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_mode')
                    ->options([
                        'cash' => 'Cash',
                        'online' => 'Online',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Action::make('add_booking')
                    ->label('Add Booking')
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->url(BookingsResource::getUrl('create'))
                    ->openUrlInNewTab(false),
                
                Action::make('view_transactions')
                    ->label('View Transactions')
                    ->color('warning')
                    ->icon('heroicon-o-banknotes')
                    ->modalHeading('Payment Transactions')
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (Bookings $record) {
                        $transactions = $record->payment_transactions;
                        if (is_string($transactions)) {
                            $transactions = json_decode($transactions, true);
                        }
                        return view('filament.components.payment-transactions-modal', ['transactions' => $transactions]);
                    })
                    ->visible(fn (Bookings $record) => $record->payment_mode === 'online' && !empty($record->payment_transactions)),
                    
                Action::make('view_members')
                    ->label('View Members')
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->modalHeading('Booking Members Details')
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(function (Bookings $record) {
                        $members = InfoGet::where('booking_id', $record->id)
                            ->orderBy('sharing_type')
                            ->orderBy('member_number')
                            ->get();
                        
                        if ($members->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('no_members')
                                    ->content('No member details found for this booking.')
                            ];
                        }
                        
                        $groupedMembers = $members->groupBy('sharing_type');
                        $sections = [];
                        
                        foreach ($groupedMembers as $sharingType => $sharingGroup) {
                            $memberFields = [];
                            foreach ($sharingGroup as $index => $member) {
                                $memberFields[] = Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make("member_{$member->id}_name")
                                            ->label('Name')
                                            ->content($member->name ?? 'N/A'),
                                        Forms\Components\Placeholder::make("member_{$member->id}_gender")
                                            ->label('Gender')
                                            ->content(ucfirst($member->gender ?? 'N/A')),
                                        Forms\Components\Placeholder::make("member_{$member->id}_contact")
                                            ->label('Contact')
                                            ->content($member->contact ?? 'N/A'),
                                        Forms\Components\Placeholder::make("member_{$member->id}_email")
                                            ->label('Email')
                                            ->content($member->email ?? 'N/A'),
                                        Forms\Components\Placeholder::make("member_{$member->id}_dob")
                                            ->label('Date of Birth')
                                            ->content($member->dob ?? 'N/A'),
                                        Forms\Components\Placeholder::make("member_{$member->id}_id_proof")
                                            ->label('ID Proof')
                                            ->content(($member->id_proof_type ?? '') . ($member->id_proof_number ? " - {$member->id_proof_number}" : '')),
                                        Forms\Components\Placeholder::make("member_{$member->id}_emergency")
                                            ->label('Emergency Contact')
                                            ->content(($member->emergency_name ?? '') . ($member->emergency_contact ? " ({$member->emergency_contact})" : '')),
                                    ]);
                                
                                if ($index < $sharingGroup->count() - 1) {
                                    $memberFields[] = Forms\Components\Placeholder::make("separator_{$member->id}")
                                        ->content('')
                                        ->extraAttributes(['class' => 'border-b my-2']);
                                }
                            }
                            
                            $sections[] = Forms\Components\Section::make("{$sharingType} Sharing")
                                ->schema($memberFields)
                                ->collapsible();
                        }
                        
                        return $sections;
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('add_booking_header')
                    ->label('Add New Booking')
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->url(BookingsResource::getUrl('create'))
                    ->openUrlInNewTab(false),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBookings::route('/create'),
            'edit' => Pages\EditBookings::route('/{record}/edit'),
        ];
    }
}