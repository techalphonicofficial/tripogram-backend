<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingsResource\Pages;
use App\Models\Bookings;
use App\Models\InfoGet;
use App\Models\Packages;
use App\Models\PackageDates;
use App\Models\ActiveCosts;
use App\Models\Coupon;
use App\Models\User;
use App\Models\PaymentLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use App\Models\Refund;

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


    // App/Filament/Resources/BookingsResource.php

    // Class ke end mein ye function add karo
    private static function sendWhatsAppAgain($booking): void
    {
        try {
            \Log::info('WhatsApp API START (Send Again Button)', [
                'booking_id' => $booking->booking_id,
                'phone' => $booking->phone
            ]);
            $bookingsssss = $booking->created_at->format('d.m.Y');

            $booking_dataas = 'booking_id:' . $booking->booking_id . ', booking_date:' . $bookingsssss;
            $package = Packages::where('id', $booking->package_id)->first();

            // Prepare active cost data 
            $activeCostArray = $booking->active_cost;
            if (is_string($activeCostArray)) {
                $activeCostArray = json_decode($activeCostArray, true);
            }

            // Calculate totals
            $gstTotal = 0;
            $subtotalWithoutGST = 0;
            $activeCostData = '';

            if (!empty($activeCostArray) && is_array($activeCostArray)) {
                $items = [];
                foreach ($activeCostArray as $item) {
                    $activityName = $item['activity'] ?? 'Unknown';
                    $cost = (float) ($item['cost'] ?? 0);
                    $quantity = (float) ($item['quantity'] ?? 1);
                    $totalWithDiscount = (float) ($item['total_with_discount'] ?? 0);
                    $totalWithGST = (float) ($item['total_with_discount_and_gst'] ?? 0);

                    $gstAmount = $totalWithGST - $totalWithDiscount;
                    $gstTotal += $gstAmount;
                    $subtotalWithoutGST += $totalWithDiscount;

                    $items[] = "{$activityName}: ₹" . number_format($cost, 2) . " × {$quantity} = ₹" . number_format($totalWithDiscount, 2);
                }
                $activeCostData = implode(' | ', $items);
            } else {
                $activeCostData = 'No activities selected';
            }

            // Format start date
            $startDate = 'Date not set';
            if ($booking->start_date) {
                $dateStr = (string) $booking->start_date;
                if (!str_contains($dateStr, '1969') && !str_contains($dateStr, '1970-01-01')) {
                    try {
                        $startDate = Carbon::parse($booking->start_date)
                            ->timezone('Asia/Kolkata')
                            ->format('d M Y');
                    } catch (\Exception $e) {
                        $startDate = 'Date not set';
                    }
                }
            }

            // ✅ Your existing WhatsApp API call
            $response = Http::post('https://api.sendinai.com/sender', [
                "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
                "phone" => $booking->phone,
                "template_name" => "booking_confirmation_enlivetrips",
                "template_language" => "EN_US",
                "text1" => $booking->full_name,
                "text2" => "https://www.enlivetrips.com/booking-detail?id=" . $booking->booking_token,
                "text3" => $booking_dataas,
                "text4" => $package->title,
                "text5" => $startDate,
                "text6" => $activeCostData,
                "text7" => "Subtotal: ₹" . number_format($subtotalWithoutGST, 2) . " | Total GST: ₹" . number_format($gstTotal, 2) . " (5%) | Grand Total: ₹" . number_format($booking->final_amount, 2),
                "text8" => "Paid: ₹" . number_format($booking->paid_amount, 2),
                "text9" => "Due: ₹" . number_format($booking->due_amount, 2),
                "text10" => "https://www.enlivetrips.com/terms-and-conditions"
            ]);

            \Log::info('WhatsApp API RESPONSE (Send Again Button)', [
                'status' => $response->status(),
                'body' => $response->body(),
                'booking_id' => $booking->booking_id
            ]);

            if ($response->successful()) {
                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title('WhatsApp Sent Successfully!')
                    ->body("Message sent to {$booking->phone}")
                    ->send();

                \Log::info('WhatsApp resent successfully for booking: ' . $booking->booking_id);
            } else {
                throw new \Exception('API failed: ' . $response->body());
            }

        } catch (\Throwable $e) {
            \Log::error('WhatsApp API FAILED (Send Again Button)', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->booking_id
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('WhatsApp Failed!')
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }
    // Helper method to get member names from InfoGet
    protected static function getMemberNames($record): string
    {
        $members = InfoGet::where('booking_id', $record->id)
            ->orderBy('member_number')
            ->get();

        if ($members->isEmpty()) {
            return '-';
        }

        $names = [];
        foreach ($members as $member) {
            if (!empty($member->name)) {
                $names[] = $member->name;
            }
        }

        return implode(', ', $names);
    }

    // Helper method to get complete member details from InfoGet
    protected static function getMemberDetails($record): string
    {
        $members = InfoGet::where('booking_id', $record->id)
            ->orderBy('sharing_type')
            ->orderBy('member_number')
            ->get();

        if ($members->isEmpty()) {
            return '-';
        }

        $membersList = [];
        $currentSharing = '';

        foreach ($members as $member) {
            // Group by sharing type
            if ($currentSharing != $member->sharing_type) {
                $currentSharing = $member->sharing_type;
                $sharingLabel = ucfirst(str_replace('_', ' ', $currentSharing));
                $membersList[] = "━━━ {$sharingLabel} Sharing ━━━";
            }

            $memberDetails = [];
            $memberDetails[] = "Name: " . ($member->name ?? 'N/A');
            $memberDetails[] = "Gender: " . ($member->gender ?? 'N/A');
            $memberDetails[] = "Contact: " . ($member->contact ?? 'N/A');
            $memberDetails[] = "Email: " . ($member->email ?? 'N/A');
            $memberDetails[] = "DOB: " . ($member->dob ?? 'N/A');
            $memberDetails[] = "ID Proof: " . (($member->id_proof_type ?? '') . ($member->id_proof_number ? " - {$member->id_proof_number}" : ''));
            $memberDetails[] = "Emergency: " . (($member->emergency_name ?? '') . ($member->emergency_contact ? " ({$member->emergency_contact})" : ''));

            $membersList[] = "► " . implode(" | ", $memberDetails);
        }

        return implode("\n", $membersList);
    }

    // Helper method for gender count from InfoGet
    protected static function getGenderCountFromInfoGet($record): string
    {
        $male = InfoGet::where('booking_id', $record->id)
            ->where('gender', 'Male')
            ->count();

        $female = InfoGet::where('booking_id', $record->id)
            ->where('gender', 'Female')
            ->count();

        $other = InfoGet::where('booking_id', $record->id)
            ->whereNotIn('gender', ['Male', 'Female'])
            ->whereNotNull('gender')
            ->count();

        return "{$male}/{$female}/{$other}";
    }

    // Helper method for total persons from InfoGet
    protected static function getTotalPersonsFromInfoGet($record): string
    {
        $total = InfoGet::where('booking_id', $record->id)->count();
        return (string) $total;
    }
    protected static function getTotalPersonsFromInfoGets($record): string
    {
        $activeCost = $record->active_cost;

        if (is_string($activeCost)) {
            $activeCost = json_decode($activeCost, true);
        }

        if (!is_array($activeCost)) {
            return '0';
        }

        $totalPersons = 0;

        foreach ($activeCost as $item) {
            if (isset($item['quantity'])) {
                $totalPersons += (int) $item['quantity'];
            }
        }

        return (string) $totalPersons;
    }
    // Helper method for gender count (from data_get JSON)
    protected static function getGenderCount($record): string
    {
        $data = $record->data_get;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            return '0/0/0';
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

        return "{$male}/{$female}/{$other}";
    }

    // Helper method for total persons from active_cost quantity
    protected static function getTotalPersons($record): string
    {
        $activeCost = $record->active_cost;
        if (is_string($activeCost)) {
            $activeCost = json_decode($activeCost, true);
        }
        if (!is_array($activeCost)) {
            return '0';
        }

        $totalPersons = 0;
        foreach ($activeCost as $item) {
            if (isset($item['quantity'])) {
                $totalPersons += (int) $item['quantity'];
            }
        }
        return (string) $totalPersons;
    }

    // Helper method for payment info
    protected static function getPaymentInfo($record): string
    {
        $result = [];
        $transactions = $record->payment_transactions;
        if (!empty($transactions)) {
            if (is_string($transactions)) {
                $transactions = json_decode($transactions, true);
            }
            if (is_array($transactions) && !empty($transactions)) {
                foreach ($transactions as $trans) {
                    if (!empty($trans['utr_number'])) {
                        $result[] = $trans['utr_number'];
                    }
                }
            }
        }
        if (empty($result) && !empty($record->payment_id)) {
            $result[] = "ID: {$record->payment_id}";
        }
        if (empty($result) && !empty($record->payment_history)) {
            $history = $record->payment_history;
            if (is_string($history)) {
                $history = json_decode($history, true);
            }
            if (is_array($history)) {
                if (isset($history['transaction_id'])) {
                    $result[] = $history['transaction_id'];
                } elseif (isset($history['order_id'])) {
                    $result[] = $history['order_id'];
                }
            }
        }
        return empty($result) ? '-' : implode(', ', $result);
    }

    // Helper method for activities
    protected static function getActivities($record): string
    {
        return collect($record->active_cost)->pluck('activity')->implode(', ');
    }

    // Helper method for coupons
    protected static function getCoupons($record): string
    {
        $appliedCoupons = $record->applied_coupons;
        if (empty($appliedCoupons)) {
            return '-';
        }
        if (is_string($appliedCoupons)) {
            $appliedCoupons = json_decode($appliedCoupons, true);
        }
        if (!is_array($appliedCoupons) || empty($appliedCoupons)) {
            return '-';
        }
        return implode(', ', $appliedCoupons);
    }

    // Helper method for payment link status
    protected static function getPaymentLinkStatus($record): string
    {
        $paymentLink = PaymentLink::where('booking_id', $record->id)
            ->orWhere('booking_id', $record->booking_id)
            ->first();

        if (!$paymentLink) {
            return 'No Link';
        }

        return match ($paymentLink->status) {
            'paid' => 'Paid',
            'pending' => 'Pending',
            'failed' => 'Failed',
            default => $paymentLink->status ?? 'No Link'
        };
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

    protected static function applyPercentageAdjustment(float $amount, float $increasePercent, float $decreasePercent): float
    {
        $afterIncrease = $amount + ($amount * $increasePercent / 100);
        $finalAmount = $afterIncrease - ($afterIncrease * $decreasePercent / 100);
        return $finalAmount;
    }


    protected static function calculateActivityTotal(callable $set, callable $get): void
    {
        $cost = (float) $get('cost');
        $discountAmount = (float) $get('discount_amount');
        $gstPercent = (float) $get('gst_percent');
        $quantity = (float) $get('quantity');

        $totalWithDiscount = ($cost - $discountAmount) * $quantity;
        $totalWithDiscountAndGst = $totalWithDiscount + ($totalWithDiscount * $gstPercent / 100);

        $set('total_with_discount', (int) round($totalWithDiscount));
        $set('total_with_discount_and_gst', (int) round($totalWithDiscountAndGst));

    }

    protected static function calculateTotalAmount(callable $set, callable $get): void
    {
        $activitiesTotal = 0;
        $activities = $get('active_cost') ?? [];

        foreach ($activities as $index => $activity) {
            $cost = (float) ($activity['cost'] ?? 0);
            $discountAmount = (float) ($activity['discount_amount'] ?? 0);
            $gstPercent = (float) ($activity['gst_percent'] ?? 0);
            $quantity = (float) ($activity['quantity'] ?? 1);

            $totalWithDiscount = ($cost - $discountAmount) * $quantity;
            $totalWithDiscountAndGst = $totalWithDiscount + ($totalWithDiscount * $gstPercent / 100);

            // ✅ Update with round
            $set("active_cost.{$index}.total_with_discount", (int) round($totalWithDiscount));
            $set("active_cost.{$index}.total_with_discount_and_gst", (int) round($totalWithDiscountAndGst));

            $activitiesTotal += $totalWithDiscountAndGst;
        }

        // ✅ Round final amount
        $finalAmount = (int) round($activitiesTotal);

        $couponDiscount = (float) ($get('total_coupon_discount') ?? 0);
        $finalAmountAfterCoupon = (int) round($finalAmount - $couponDiscount);

        $set('final_amount', $finalAmountAfterCoupon > 0 ? $finalAmountAfterCoupon : 0);

        $transactions = $get('payment_transactions') ?? [];
        $totalPaid = 0;
        foreach ($transactions as $transaction) {
            $totalPaid += (int) round((float) ($transaction['amount'] ?? 0));
        }

        $set('paid_amount', $totalPaid);
        $set('due_amount', (int) round($finalAmountAfterCoupon - $totalPaid));
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
                                Forms\Components\TextInput::make('booking_id')

                                    ->required()

                                    ->hiddenOn('create')
                                    ->visibleOn('edit'),
                                Forms\Components\TextInput::make('gst_no')
                                    ->tel()
                                    ->nullable()
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
                                            ->where('status', 'open')
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

                                            // ✅ Dropdown mein original date hi dikhao (NO +1)
                                            $options[$packageDate->start_date] = $startDate->format('d M, Y') . ' to ' . $endDate->format('d M, Y') . $badge;
                                        }

                                        return $options;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $packageDate = PackageDates::where('package_id', $get('package_id'))
                                                ->where('start_date', $state)
                                                ->first();

                                            if ($packageDate) {
                                                $set('end_date', $packageDate->end_date);
                                                $set('package_date_id', $packageDate->id);

                                                $increasePercent = (float) ($packageDate->increase_amount_by_percent ?? 0);
                                                $decreasePercent = (float) ($packageDate->decrease_amount_by_percent ?? 0);

                                                $set('increase_percent', $increasePercent);
                                                $set('decrease_percent', $decreasePercent);

                                                $basePrice = (float) ($packageDate->starting_price ?? 0);
                                                $finalBasePrice = static::applyPercentageAdjustment($basePrice, $increasePercent, $decreasePercent);

                                                $set('base_price', $finalBasePrice);

                                                $activities = $get('active_cost') ?? [];
                                                $updatedActivities = [];

                                                foreach ($activities as $index => $activity) {
                                                    if (isset($activity['activity']) && $activity['activity']) {
                                                        $originalCost = (float) ($activity['original_cost'] ?? $activity['cost'] ?? 0);
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

                                                            $discountAmount = (float) ($activity['discount_amount'] ?? 0);
                                                            $gstPercent = (float) ($activity['gst_percent'] ?? 0);
                                                            $quantity = (float) ($activity['quantity'] ?? 1);

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
                                            // ✅ Sirf default display ke liye +1 day, value same rahegi
                                            return $record->start_date;
                                        }
                                        return null;
                                    })
                                    ->formatStateUsing(function ($state) {
                                        // ✅ Sirf selected value display karne ke liye +1 day
                                        if ($state) {
                                            try {
                                                return Carbon::parse($state)->addDay()->format('d M, Y');
                                            } catch (\Exception $e) {
                                                return $state;
                                            }
                                        }
                                        return $state;
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

                        Forms\Components\Repeater::make('active_cost')
                            ->label('Activities')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Hidden::make('package_id'),
                                Forms\Components\Hidden::make('original_cost')->default(0),

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
                                            'show_on_website' => 0,
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
                                                $originalCost = (float) $activityData->cost;
                                                $gstPercent = (float) ($activityData->gst_percent ?? 0);
                                                $discountAmount = (float) ($activityData->discount_amount ?? 0);

                                                $increasePercent = (float) ($get('../../increase_percent') ?? 0);
                                                $decreasePercent = (float) ($get('../../decrease_percent') ?? 0);

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

                                // ✅ CORRECT QUANTITY FIELD
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->live(onBlur: false)
                                    ->live(debounce: 300)
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
                            ->live()
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
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $set('payment_type', null);
                                        static::calculateTotalAmount($set, $get);
                                    }),

                                // Payment Transactions Repeater - For ALL modes
                                Forms\Components\Repeater::make('payment_transactions')
                                    ->label('Payment Transactions')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('utr_number')
                                                    ->label(function (callable $get) {
                                                        $mode = $get('../../payment_mode');
                                                        return $mode === 'cash' ? 'Receipt / Reference No' : 'UTR Number / Transaction ID';
                                                    })
                                                    ->maxLength(255)
                                                    ->placeholder(function (callable $get) {
                                                        $mode = $get('../../payment_mode');
                                                        return $mode === 'cash' ? 'Enter receipt number' : 'Enter UTR number';
                                                    }),

                                                Forms\Components\DatePicker::make('transaction_date')
                                                    ->label('Transaction Date')
                                                    ->default(now()),

                                                Forms\Components\TextInput::make('amount')
                                                    ->label('Transaction Amount')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required()
                                                    ->live(onBlur: false)
                                                    ->live(debounce: 300)
                                                    ->reactive()
                                                    ->placeholder('Enter amount'),

                                                Forms\Components\Select::make('payment_type')
                                                    ->label('Payment Type')
                                                    ->options([
                                                        'full' => 'Full Payment',
                                                        'half' => 'Half Payment',
                                                        'partial' => 'Partial Payment',
                                                    ])
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
                                            ->helperText('Upload payment screenshot (Max 5 images, 2MB each)'),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Additional Notes')


                                            ->placeholder('Any additional information about this transaction'),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->default([])
                                    ->visible(fn(callable $get) => in_array($get('payment_mode'), ['cash', 'online', 'other']))
                                    ->minItems(0)
                                    ->maxItems(10)
                                    ->live()
                                    ->reactive()
                                    ->itemLabel(fn(array $state): ?string => $state['utr_number'] ?? 'New Transaction')
                                    ->createItemButtonLabel('Add Transaction')
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        $transactions = $get('payment_transactions') ?? [];
                                        $totalPaid = 0;
                                        foreach ($transactions as $transaction) {
                                            $totalPaid += (float) ($transaction['amount'] ?? 0);
                                        }
                                        $set('paid_amount', $totalPaid);
                                        static::calculateTotalAmount($set, $get);
                                    }),

                                Forms\Components\Hidden::make('payment_type')->default(null),

                                Forms\Components\TextInput::make('final_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->step(1)
                                    ->minValue(0)
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->formatStateUsing(fn($state) => (int) $state), // ✅ Force integer display

                                Forms\Components\TextInput::make('paid_amount')
                                    ->numeric()
                                    ->step(1)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => (int) $state), // ✅ Force integer display

                                Forms\Components\TextInput::make('due_amount')
                                    ->numeric()
                                    ->step(1)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => (int) $state), // ✅ Force integer display
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'confirmed' => 'Confirmed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('confirmed')
                                    ->required(),
                            ]),


                        Forms\Components\Section::make('Coupon Information')
                            ->schema([
                                Forms\Components\Select::make('applied_coupons')
                                    ->label('Select Coupons')
                                    ->multiple()
                                    ->options(function () {
                                        return Coupon::where('is_used', false)
                                            ->where('expires_at', '>', Carbon::now())
                                            ->where('discount_value', '>', 0)
                                            ->pluck('code', 'code')
                                            ->toArray();
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $totalDiscount = 0;

                                        $activitiesTotal = 0;
                                        $activities = $get('active_cost') ?? [];
                                        foreach ($activities as $activity) {
                                            if (isset($activity['total_with_discount_and_gst'])) {
                                                $activitiesTotal += (float) $activity['total_with_discount_and_gst'];
                                            }
                                        }

                                        $coupons = $state ?? [];

                                        foreach ($coupons as $couponCode) {
                                            $coupon = Coupon::where('code', $couponCode)->first();
                                            if ($coupon) {
                                                $discountValue = (float) $coupon->discount_value;

                                                if ($coupon->discount_type === 'percentage') {
                                                    $discount = ($activitiesTotal * $discountValue) / 100;
                                                } else {
                                                    $discount = $discountValue;
                                                }

                                                $totalDiscount += $discount;
                                            }
                                        }

                                        $set('total_coupon_discount', $totalDiscount);
                                        static::calculateTotalAmount($set, $get);

                                        if (!empty($coupons)) {
                                            Notification::make()
                                                ->title('Coupons Applied')
                                                ->body('Total discount: ₹' . number_format($totalDiscount, 2))
                                                ->success()
                                                ->send();
                                        }
                                    }),

                                Forms\Components\TextInput::make('total_coupon_discount')
                                    ->label('Total Coupon Discount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('₹')
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('special_note')

                                    ->nullable()
                                    ->maxLength(10),
                                Forms\Components\Placeholder::make('coupon_info')
                                    ->label('')
                                    ->content('Only unused and non-expired coupons are shown')
                                    ->extraAttributes(['class' => 'text-sm text-gray-500 mt-2']),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->extraAttributes(['class' => 'package-resource-form']);
    }

    public static function table(Table $table): Table
    {

        $table->modifyQueryUsing(function (Builder $query) {
            $query->where('status', '!=', 'pending');
        });
        return $table
            ->columns([
                Tables\Columns\SelectColumn::make('assign_to')
                    ->label('Sales Person')
                    ->options(function () {
                        return User::whereIn('role', ['admin', 'sales'])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder('✏️ Edit')
                    ->selectablePlaceholder(true)
                    ->default(null)
                    ->visible(fn() => auth()->user()->role === 'admin')
                    ->updateStateUsing(function ($record, $state) {
                        $assignedTo = empty($state) ? null : $state;
                        $record->update(['assign_to' => $assignedTo]);

                        if ($assignedTo) {
                            $user = User::find($assignedTo);
                            Notification::make()
                                ->title('Booking Assigned')
                                ->body("Booking #{$record->booking_id} assigned to {$user->name}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Booking Unassigned')
                                ->body("Booking #{$record->booking_id} has been unassigned")
                                ->warning()
                                ->send();
                        }
                    })
                    ->width('80px'),
                Tables\Columns\TextColumn::make('booking_id')->searchable()->toggleable()->width('120px'),
                Tables\Columns\TextColumn::make('created_at')->label('Booking Date/Time')->dateTime('d M Y H:i')->width('130px'),
                Tables\Columns\TextColumn::make('package_title')->label('Package')->searchable()->toggleable()->width('180px')->limit(25),
                Tables\Columns\TextColumn::make('start_date')->label('Dep Date')->date('d M Y')->toggleable()->width('100px'),
                Tables\Columns\TextColumn::make('full_name')->label('name')->searchable()->toggleable()->width('150px'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Contact No')  // ✅ 'label' hai, 'lable' nahi
                    ->searchable()
                    ->toggleable()
                    ->width('100px'),


                Tables\Columns\TextColumn::make('total_persons')
                    ->label('Total Persons')
                    ->getStateUsing(function ($record) {
                        return self::getTotalPersons($record);
                    })
                    ->toggleable()
                    ->width('80px')
                    ->alignCenter()
                    ->color('success')
                    ->badge(),




                Tables\Columns\TextColumn::make('gender_count')
                    ->label('M/F/O')
                    ->getStateUsing(function ($record) {
                        return self::getGenderCountFromInfoGet($record);
                    })
                    ->toggleable()
                    ->width('80px'),
                Tables\Columns\TagsColumn::make('active_cost')
                    ->label('Sharing')
                    ->getStateUsing(fn($record) => collect($record->active_cost)->pluck('activity')->toArray())
                    ->toggleable()
                    ->width('150px')
                    ->limitList(2),
                Tables\Columns\SelectColumn::make('booking_type')
                    ->label('Booking Type')
                    ->options([
                        'B2B' => 'B2B',
                        'B2C' => 'B2C',
                    ])
                    ->placeholder('Select Type')
                    ->selectablePlaceholder(true)
                    ->default(null)
                    ->sortable()
                    ->updateStateUsing(function ($record, $state) {

                        $record->update([
                            'booking_type' => $state ?: null,
                        ]);

                        if ($state) {
                            \Filament\Notifications\Notification::make()
                                ->title('Booking Type Updated')
                                ->body("Booking #{$record->booking_id} set to: {$state}")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Booking Type Removed')
                                ->body("Booking type removed from booking #{$record->booking_id}")
                                ->warning()
                                ->send();
                        }
                    })
                    ->width('150px'),

                Tables\Columns\TextColumn::make('source')->label('Mode')->searchable()->toggleable()->width('150px'),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Sales Person')
                    ->searchable()
                    ->toggleable()
                    ->width('150px')
                    ->placeholder('Unassigned'),
                Tables\Columns\BadgeColumn::make('payment_mode')->label('Payment Mode')->colors(['success' => 'cash', 'warning' => 'online', 'gray' => 'other'])->toggleable()->width('90px'),



                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('INR', true)
                    ->color(function ($record): ?string {
                        $appliedCoupons = $record->applied_coupons;
                        if (is_string($appliedCoupons)) {
                            $appliedCoupons = json_decode($appliedCoupons, true);
                        }
                        return (!empty($appliedCoupons)) ? 'success' : null;
                    })
                    ->toggleable()
                    ->width('90px'),


                Tables\Columns\TextColumn::make('total_coupon_discount')
                    ->label('Coupen Amount')
                    ->money('INR', true)
                    ->toggleable()
                    ->width('90px'),
                Tables\Columns\TagsColumn::make('applied_coupons')
                    ->label('Coupons')
                    ->getStateUsing(function ($record) {
                        $appliedCoupons = $record->applied_coupons;
                        if (empty($appliedCoupons)) {
                            return ['-'];
                        }
                        if (is_string($appliedCoupons)) {
                            $appliedCoupons = json_decode($appliedCoupons, true);
                        }
                        if (!is_array($appliedCoupons) || empty($appliedCoupons)) {
                            return ['-'];
                        }
                        return $appliedCoupons;
                    })
                    ->badge()
                    ->color(fn($state): string => $state === '-' ? 'gray' : 'primary')
                    ->toggleable()
                    ->width('120px')
                    ->limitList(2),




                Tables\Columns\TextColumn::make('amount_before_coupon')
                    ->label('Final Amount')
                    ->money('INR', true)
                    ->getStateUsing(function ($record) {
                        $finalAmount = (float) ($record->final_amount ?? 0);
                        $couponDiscount = (float) ($record->total_coupon_discount ?? 0);
                        return $finalAmount + $couponDiscount;
                    })
                    ->toggleable()
                    ->width('100px'),


                Tables\Columns\TextColumn::make('paid_amount')->money('INR', true)->toggleable()->width('90px'),
                Tables\Columns\TextColumn::make('razorpay_link')
                    ->label('Payment Link')
                    ->getStateUsing(function ($record) {
                        $paymentLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();
                        return $paymentLink?->payment_link;
                    })
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($state) => $state ? '🔗 Pay Now' : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_amount')->money('INR', true)->toggleable()->width('90px')->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('razorpay_amount')
                    ->label('Link Amount')
                    ->getStateUsing(function ($record) {
                        $paymentLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();
                        return $paymentLink?->amount;
                    })
                    ->money('INR', true)
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('razorpay_status')
                    ->label('Balance Amount Status')
                    ->getStateUsing(function ($record) {
                        $paymentLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();
                        return $paymentLink?->status ?? 'no_link';
                    })
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'gray' => 'no_link',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        default => 'No Link'
                    })
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('razorpay_send_status')
                    ->label('Razorpay Status')
                    ->getStateUsing(function ($record) {
                        $paymentLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();

                        if (!$paymentLink)
                            return 'no_link';

                        return $paymentLink->cron_job == 1 ? 'sent' : 'not_sent';
                    })
                    ->colors([
                        'success' => 'sent',
                        'danger' => 'not_sent',
                        'gray' => 'no_link',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'sent' => 'Sent',
                        'not_sent' => 'Not Sent',
                        default => 'No Link'
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('member_names')
                    ->label('Member Names')
                    ->getStateUsing(function ($record) {
                        return self::getMemberNames($record);
                    })
                    ->toggleable()
                    ->width('200px')
                    ->limit(30),

                Tables\Columns\TextColumn::make('booking_token')
                    ->label('Link')
                    ->url(fn($record) => 'https://tripogramclub.com/booking-detail?id=' . $record->booking_token)
                    ->openUrlInNewTab()
                    ->searchable()
                    ->toggleable()
                    ->width('80px')
                    ->formatStateUsing(fn($state) => '🔗 View'),

                Tables\Columns\TextColumn::make('payment_info')
                    ->label('Payment Details')
                    ->getStateUsing(function ($record) {
                        return self::getPaymentInfo($record);
                    })
                    ->limit(30)
                    ->toggleable()
                    ->width('180px')
                    ->tooltip(function ($record) {
                        $tooltip = [];
                        $transactions = $record->payment_transactions;
                        if (!empty($transactions)) {
                            if (is_string($transactions)) {
                                $transactions = json_decode($transactions, true);
                            }
                            if (is_array($transactions) && !empty($transactions)) {
                                $tooltip[] = "━━━ Transactions ━━━";
                                foreach ($transactions as $trans) {
                                    $tooltip[] = "UTR: {$trans['utr_number']}";
                                    $tooltip[] = "Amount: ₹{$trans['amount']}";
                                    $tooltip[] = "Date: {$trans['transaction_date']}";
                                    $tooltip[] = "Type: {$trans['payment_type']}";
                                    $tooltip[] = "━━━━━━━━━━━━━━━━";
                                }
                            }
                        }
                        if (!empty($record->payment_id)) {
                            $tooltip[] = "Payment ID: {$record->payment_id}";
                        }
                        if (!empty($record->payment_history)) {
                            $history = $record->payment_history;
                            if (is_string($history)) {
                                $history = json_decode($history, true);
                            }
                            if (is_array($history)) {
                                $tooltip[] = "━━━ History ━━━";
                                foreach ($history as $key => $value) {
                                    if (!is_array($value)) {
                                        $tooltip[] = "{$key}: {$value}";
                                    }
                                }
                            }
                        }
                        if (!empty($record->due_amount) && $record->due_amount > 0) {
                            $tooltip[] = "⚠️ Due: ₹{$record->due_amount}";
                        }
                        return empty($tooltip) ? null : implode("\n", $tooltip);
                    }),

                Tables\Columns\ImageColumn::make('payment_screenshots')
                    ->label('SS')
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
                                $screenshots = array_merge($screenshots, (array) $transaction['screenshot']);
                            }
                        }
                        return $screenshots;
                    })
                    ->circular(false)
                    ->width(40)
                    ->height(40)
                    ->stacked()
                    ->limit(2)
                    ->toggleable()
                    ->extraAttributes(['style' => 'padding: 4px 0;']),




                Tables\Columns\SelectColumn::make('batch_id')
                    ->label('Batch')
                    ->options(\App\Models\Batch::pluck('batch_name', 'id')->toArray())
                    ->searchable()
                    ->sortable()
                    ->placeholder('Select Batch')
                    ->selectablePlaceholder(true)
                    ->default(null)
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['batch_id' => empty($state) ? null : $state]);

                        if ($state) {
                            $batch = \App\Models\Batch::find($state);
                            \Filament\Notifications\Notification::make()
                                ->title('Batch Assigned')
                                ->body("Booking #{$record->booking_id} assigned to batch: {$batch->batch_name}")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Batch Removed')
                                ->body("Batch removed from booking #{$record->booking_id}")
                                ->warning()
                                ->send();
                        }
                    })
                    ->width('150px'),

                Tables\Columns\TextColumn::make('due_amount')->money('INR', true)->toggleable()->width('90px')->color(fn($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\BadgeColumn::make('payment_type')->label('Type')->colors(['success' => 'full', 'warning' => 'half', 'info' => 'partial'])->toggleable()->width('90px'),
                Tables\Columns\BadgeColumn::make('status')->colors(['warning' => 'pending', 'success' => 'confirmed', 'danger' => 'cancelled'])->toggleable()->width('100px'),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable()->width('180px'),
                Tables\Columns\TextColumn::make('package.destination.name')->label('Destination'),

                Tables\Columns\TextColumn::make('end_date')->date('d M Y')->toggleable()->width('100px'),
            ])
            ->defaultSort('id', 'desc')

            ->filters([
                SelectFilter::make('package_id')
                    ->label('Filter by Package')
                    ->options(function () {
                        return Packages::orderBy('title')->pluck('title', 'id')->toArray();
                    })
                    ->placeholder('All Packages')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('assign_to')
                    ->label('Filter by Assigned User')
                    ->options(function () {
                        return User::whereIn('role', ['admin', 'sales'])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('All Users')
                    ->searchable()
                    ->preload(),

                Filter::make('start_date')
                    ->label('Start Date')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->displayFormat('d M Y')
                            ->format('Y-m-d'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['start_date'], fn($q, $date) => $q->whereDate('start_date', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators['start_date'] = 'Start Date: ' . Carbon::parse($data['start_date'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                Filter::make('created_date')
                    ->label('Booking Date')
                    ->form([
                        DatePicker::make('created_date')
                            ->label('Booking Date')
                            ->displayFormat('d M Y')
                            ->format('Y-m-d'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_date'], fn($q, $date) => $q->whereDate('created_at', $date));  // ✅ created_at column use karo
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_date'] ?? null) {
                            $indicators['created_date'] = 'Booking Date: ' . Carbon::parse($data['created_date'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([

                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->placeholder('All Status'),
                SelectFilter::make('payment_link_status')
                    ->label('Filter By Balance Payment')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'no_link' => 'No Link',
                    ])
                    ->placeholder('All Payment Statuses')
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $status) {
                                if ($status === 'no_link') {
                                    return $query->whereDoesntHave('paymentLinks');
                                }
                                return $query->whereHas('paymentLinks', function ($q) use ($status) {
                                    $q->where('status', $status);
                                });
                            }
                        );
                    }),
                SelectFilter::make('payment_mode')
                    ->label('Filter by Payment Mode')
                    ->options([
                        'cash' => 'Cash',
                        'online' => 'Online',
                        'other' => 'Other',
                    ])
                    ->placeholder('All Modes'),

                Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\TextInput::make('min')->label('Min Amount')->numeric()->placeholder('0'),
                        Forms\Components\TextInput::make('max')->label('Max Amount')->numeric()->placeholder('Any'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'], fn($q, $amount) => $q->where('final_amount', '>=', $amount))
                            ->when($data['max'], fn($q, $amount) => $q->where('final_amount', '<=', $amount));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['min']) && !empty($data['max'])) {
                            $indicators[] = 'Amount: ₹' . $data['min'] . ' - ₹' . $data['max'];
                        } elseif (!empty($data['min'])) {
                            $indicators[] = 'Min Amount: ₹' . $data['min'];
                        } elseif (!empty($data['max'])) {
                            $indicators[] = 'Max Amount: ₹' . $data['max'];
                        }
                        return $indicators;
                    }),

                TernaryFilter::make('due_amount')
                    ->label('Has Due Amount')
                    ->placeholder('All Bookings')
                    ->trueLabel('Has Due Amount')
                    ->falseLabel('No Due Amount')
                    ->queries(
                        true: fn(Builder $query) => $query->where('due_amount', '>', 0),
                        false: fn(Builder $query) => $query->where('due_amount', 0),
                        blank: fn(Builder $query) => $query,
                    ),


            ])
            ->filtersFormColumns(2)
            ->filtersFormWidth('4xl')
            ->persistFiltersInSession()
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
                    ->visible(fn(Bookings $record) => $record->payment_mode === 'online' && !empty($record->payment_transactions)),

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

                Action::make('generate_payment_link')
                    ->label('Generate Payment Link')
                    ->color('warning')
                    ->icon('heroicon-o-link')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Payment Link')

                    ->modalDescription(function (Bookings $record) {

                        $dueAmount = $record->due_amount ?? 0;

                        if ($dueAmount <= 0) {
                            return 'This booking has no due amount.';
                        }

                        $existingLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();

                        if ($existingLink && $existingLink->payment_link) {
                            return "Existing payment link found.\n\nA new payment link will be regenerated with updated amount ₹" . number_format($dueAmount, 2);
                        }

                        return "Generate payment link for ₹" . number_format($dueAmount, 2) . "?";
                    })

                    ->modalSubmitActionLabel('Generate Link')

                    ->visible(function (Bookings $record) {

                        $dueAmount = $record->due_amount ?? 0;

                        if ($dueAmount <= 0) {
                            return false;
                        }

                        $existingLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();

                        return !$existingLink || $existingLink->status !== 'paid';
                    })

                    ->action(function ($record) {

                        try {

                            $dueAmount = $record->due_amount ?? 0;

                            if ($dueAmount <= 0) {

                                Notification::make()
                                    ->title('No Due Amount')
                                    ->body('This booking has no pending amount.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $api = new \Razorpay\Api\Api(
                                env('RAZORPAY_KEY'),
                                env('RAZORPAY_SECRET')
                            );

                            $startDate = Carbon::parse($record->start_date);

                            $findpackage = Packages::find($record->package_id);

                            $days = $findpackage->day ?? 1;

                            $expireDate = $startDate->copy()->subDays($days);

                            // Existing link check
                            $existingLink = PaymentLink::where('booking_id', $record->id)
                                ->orWhere('booking_id', $record->booking_id)
                                ->first();

                            // Cancel old link if exists
                            if ($existingLink && $existingLink->razorpay_link_id) {

                                try {

                                    $api->paymentLink
                                        ->fetch($existingLink->razorpay_link_id)
                                        ->cancel();

                                } catch (\Exception $e) {

                                    \Log::warning(
                                        'Old Razorpay link cancel failed: ' . $e->getMessage()
                                    );
                                }
                            }

                            // Create NEW Razorpay link
                            $razorpayPaymentLink = $api->paymentLink->create([

                                'amount' => $dueAmount * 100,

                                'currency' => 'INR',

                                'accept_partial' => false,

                                'description' => "Balance payment for booking {$record->booking_id}",

                                'customer' => [
                                    'name' => $record->full_name,
                                    'email' => $record->email,
                                    'contact' => $record->phone,
                                ],

                                'notify' => [
                                    'sms' => false,
                                    'email' => false,
                                ],

                                'reminder_enable' => true,

                                'notes' => [
                                    'booking_id' => $record->booking_id,
                                    'payment_type' => 'balance',
                                ],

                                'callback_url' => 'https://tripogramclub.com/payment-callback',

                                'callback_method' => 'get',
                            ]);

                            // Update existing OR create new
                            if ($existingLink) {

                                $existingLink->update([

                                    'booking_token' => $record->booking_token,

                                    'razorpay_link_id' => $razorpayPaymentLink->id,

                                    'payment_link' => $razorpayPaymentLink->short_url,

                                    'amount' => $dueAmount,

                                    'expire_at' => $expireDate->format('Y-m-d'),

                                    'status' => 'pending',
                                ]);

                            } else {

                                PaymentLink::create([

                                    'booking_id' => $record->id,

                                    'booking_token' => $record->booking_token,

                                    'razorpay_link_id' => $razorpayPaymentLink->id,

                                    'payment_link' => $razorpayPaymentLink->short_url,

                                    'amount' => $dueAmount,

                                    'expire_at' => $expireDate->format('Y-m-d'),

                                    'status' => 'pending',
                                ]);
                            }

                            Notification::make()
                                ->title('Payment Link Generated Successfully!')
                                ->body(
                                    "Payment link generated for ₹" .
                                    number_format($dueAmount, 2)
                                )
                                ->success()
                                ->send();

                        } catch (\Exception $e) {

                            \Log::error(
                                'Payment Link generation failed: ' . $e->getMessage()
                            );

                            Notification::make()
                                ->title('Payment Link Generation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('delete_payment_link')
                    ->label('Delete Payment Link')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Payment Link')
                    ->modalDescription(function (Bookings $record) {
                        $paymentLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();

                        if (!$paymentLink) {
                            return 'No payment link exists for this booking.';
                        }
                        return 'Are you sure you want to delete the payment link for booking #' . $record->booking_id . '? Amount: ₹' . number_format($paymentLink->amount, 2);
                    })
                    ->modalSubmitActionLabel('Yes, Delete')
                    ->visible(function (Bookings $record) {
                        $paymentLink = PaymentLink::where('booking_id', $record->id)
                            ->orWhere('booking_id', $record->booking_id)
                            ->first();
                        // Show only if payment link exists and user is admin
                        return $paymentLink && auth()->user()->role === 'admin';
                    })
                    ->action(function ($record) {
                        try {
                            $paymentLink = PaymentLink::where('booking_id', $record->id)
                                ->orWhere('booking_id', $record->booking_id)
                                ->first();

                            if (!$paymentLink) {
                                Notification::make()
                                    ->title('No Payment Link Found')
                                    ->body('This booking does not have a payment link to delete.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Optional: Also try to delete from Razorpay (if you have the API key)
                            // This part is optional - uncomment if you want to delete from Razorpay too
                            /*
                            try {
                                $api = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
                                if ($paymentLink->razorpay_link_id) {
                                    $api->paymentLink->fetch($paymentLink->razorpay_link_id)->cancel();
                                    \Log::info('Razorpay payment link cancelled: ' . $paymentLink->razorpay_link_id);
                                }
                            } catch (\Exception $e) {
                                \Log::warning('Could not cancel Razorpay payment link: ' . $e->getMessage());
                                // Continue with deletion from database even if Razorpay fails
                            }
                            */

                            // Delete from database
                            $paymentLink->delete();

                            Notification::make()
                                ->title('Payment Link Deleted!')
                                ->body('Payment link has been successfully deleted for booking #' . $record->booking_id)
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Log::error('Payment Link deletion failed: ' . $e->getMessage());
                            Notification::make()
                                ->title('Payment Link Deletion Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('add_refund')
                    ->label('Add Refund')
                    ->color('danger')
                    ->icon('heroicon-o-currency-rupee')
                    ->requiresConfirmation()
                    ->modalHeading('Add Refund Details')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->required()
                            ->prefix('₹')
                            ->default(fn($record) => $record->paid_amount),

                        Forms\Components\Select::make('type')
                            ->label('Refund Type')
                            ->options([
                                'full' => 'Full Refund',
                                'partial' => 'Partial Refund',
                            ])
                            ->default('full')
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Refund Reason')
                            ->required()
                            ->rows(2),

                        Forms\Components\FileUpload::make('image')
                            ->label('Refund Proof')
                            ->image()
                            ->directory('refunds')
                            ->maxSize(2048),
                    ])
                    ->action(function ($record, array $data) {
                        // Refund table mein save karo
                        \App\Models\Refund::create([
                            'booking_id' => $record->id,
                            'amount' => $data['amount'],
                            'type' => $data['type'],
                            'reason' => $data['reason'],
                            'image' => $data['image'] ?? null,
                        ]);

                        // Booking status cancelled karo
            

                        \Filament\Notifications\Notification::make()
                            ->title('Refund Added!')
                            ->body('Refund details saved successfully for booking #' . $record->booking_id)
                            ->success()
                            ->send();
                    }),

                Action::make('view_refund')
                    ->label('View Refund')
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->modalHeading('Refund Details')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn($record) => view('filament.components.refund-modal', [
                        'refund' => \App\Models\Refund::where('booking_id', $record->id)->first()
                    ]))
                    ->visible(fn($record) => \App\Models\Refund::where('booking_id', $record->id)->exists()),

                Action::make('send_again')
                    ->label('Send Again')
                    ->color('success')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading('Send WhatsApp Again')
                    ->modalDescription('Are you sure you want to resend WhatsApp confirmation for this booking?')
                    ->modalSubmitActionLabel('Yes, Send')
                    ->action(function ($record) {
                        self::sendWhatsAppAgain($record);
                    })
                    ->visible(fn(Bookings $record) => $record->status === 'confirmed' || 'pending' || 'cancelled'), // Sirf confirmed bookings ke liye

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {

                            return response()->streamDownload(function () use ($records) {
                                $handle = fopen('php://output', 'w');
                                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                                // Headers
                                fputcsv($handle, [
                                    'ID',
                                    'Booking ID',
                                    'Full Name',
                                    'Email',
                                    'Phone',
                                    'Package',
                                    'Total Persons',
                                    'M/F/O',
                                    'Member Names',
                                    'Member Details',
                                    'Link',
                                    'Payment Details',
                                    'Activities',
                                    'Before Coupon (₹)',
                                    'Final Amount (₹)',
                                    'Discount (₹)',
                                    'Coupons',
                                    'Payment Link',
                                    'Link Amount',
                                    'Link Status',
                                    'Assign To',
                                    'Paid Amount (₹)',
                                    'Due Amount (₹)',
                                    'Payment Mode',
                                    'Payment Type',
                                    'Status',
                                    'Start Date',
                                    'End Date',
                                    'Booking Date/Time'
                                ]);

                                foreach ($records as $record) {
                                    $paymentLink = PaymentLink::where('booking_id', $record->id)
                                        ->where('status', '!=', 'pending')
                                        ->orWhere('booking_id', $record->booking_id)
                                        ->first();

                                    fputcsv($handle, [
                                        $record->id,
                                        $record->booking_id,
                                        $record->full_name,
                                        $record->email,
                                        $record->phone,
                                        $record->package_title,
                                        self::getTotalPersons($record),
                                        self::getGenderCountFromInfoGet($record),
                                        self::getMemberNames($record),
                                        self::getMemberDetails($record),
                                        'https://tripogramclub.com/booking-detail?id=' . $record->booking_token,
                                        self::getPaymentInfo($record),
                                        self::getActivities($record),
                                        ($record->final_amount ?? 0) + ($record->total_coupon_discount ?? 0),
                                        $record->final_amount,
                                        $record->total_coupon_discount,
                                        self::getCoupons($record),
                                        $paymentLink?->payment_link ?? '-',
                                        $paymentLink?->amount ?? '-',
                                        self::getPaymentLinkStatus($record),
                                        $record->assignedUser?->name ?? 'Unassigned',
                                        $record->paid_amount,
                                        $record->due_amount,
                                        $record->payment_mode,
                                        $record->payment_type,
                                        $record->status,
                                        $record->start_date ? Carbon::parse($record->start_date)->format('Y-m-d') : '',
                                        $record->end_date ? Carbon::parse($record->end_date)->format('Y-m-d') : '',
                                        $record->created_at ? Carbon::parse($record->created_at)->format('Y-m-d H:i:s') : '',
                                    ]);
                                }

                                fclose($handle);
                            }, 'bookings-export-' . now()->format('Y-m-d-His') . '.csv');
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('export_all')
                    ->label('Export All')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($livewire) {
                        $query = Bookings::query()->orderBy('id', 'desc');
                        $query->where('status', '!=', 'pending');
                        $filters = $livewire->tableFilters;
                        $search = $livewire->tableSearch;
                        if (!empty($search)) {
                            $query->where(function ($q) use ($search) {
                                $q->where('booking_id', 'like', "%{$search}%")
                                    ->orWhere('full_name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->orWhere('package_title', 'like', "%{$search}%");
                            });
                        }
                        // ✅ Apply filters correctly
                        if (!empty($filters['package_id']['value'])) {
                            $query->where('package_id', $filters['package_id']['value']);
                        }
                        if (!empty($filters['assign_to']['value'])) {
                            $query->where('assign_to', $filters['assign_to']['value']);
                        }
                        // In the export_all action - add this after other filters
                        if (!empty($filters['payment_link_status']['value'])) {
                            $status = $filters['payment_link_status']['value'];
                            if ($status === 'no_link') {
                                $query->whereDoesntHave('paymentLinks');
                            } else {
                                $query->whereHas('paymentLinks', function ($q) use ($status) {
                                    $q->where('status', $status);
                                });
                            }
                        }
                        if (!empty($filters['start_date']['start_date'])) {  // ✅ Fixed: start_date filter
                            $query->whereDate('start_date', $filters['start_date']['start_date']);
                        }
                        if (!empty($filters['created_date']['created_date'])) {  // ✅ ADDED: created_date filter
                            $query->whereDate('created_at', $filters['created_date']['created_date']);
                        }
                        if (!empty($filters['status']['value'])) {
                            $query->where('status', $filters['status']['value']);
                        }
                        if (!empty($filters['payment_mode']['value'])) {
                            $query->where('payment_mode', $filters['payment_mode']['value']);
                        }
                        if (!empty($filters['amount_range']['min'])) {
                            $query->where('final_amount', '>=', $filters['amount_range']['min']);
                        }
                        if (!empty($filters['amount_range']['max'])) {
                            $query->where('final_amount', '<=', $filters['amount_range']['max']);
                        }
                        if (!empty($filters['due_amount']['value'])) {
                            if ($filters['due_amount']['value'] === true) {
                                $query->where('due_amount', '>', 0);
                            } elseif ($filters['due_amount']['value'] === false) {
                                $query->where('due_amount', 0);
                            }
                        }

                        $records = $query->get();

                        return response()->streamDownload(function () use ($records) {
                            $handle = fopen('php://output', 'w');
                            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                            // Headers
                            fputcsv($handle, [
                                'S.no',
                                'Bookid',
                                'Bookid Date',
                                'Destination',
                                'Dep date',
                                'Name',
                                'Contact No',
                                'Total no',
                                'Male',
                                'Female',
                                'Sharing',
                                'type',
                                'Mode(manual/Online)',
                                'Sales person',
                                'Total',
                                'Coupon Amt',
                                'Coupons',
                                'Discount',
                                'Final total',
                                'Paid Amount',
                                'Link',
                                'Balance amount',
                                'Link status',
                                'Balance Status'
                            ]);

                            $sno = 1;
                            foreach ($records as $record) {
                                // Get destination
                                $destination = $record->package->title ?? $record->title ?? '';

                                // Get mode
                                $mode = $record->payment_mode == 'online' ? 'Online' : ($record->payment_mode == 'cash' ? 'Manual' : $record->payment_mode);

                                // Get sales person
                                $salesPerson = $record->assignedUser?->name ?? ($record->assign_to ?? '');

                                // Get booking type
                                $bookingType = $record->booking_type ?? '';

                                // ✅ SIMPLE: Get sharing from active_cost directly
                                $sharing = 'N/A';
                                if ($record->active_cost && is_array($record->active_cost)) {
                                    $sharingTypes = [];
                                    foreach ($record->active_cost as $costItem) {
                                        if (isset($costItem['activity'])) {
                                            $sharingTypes[] = $costItem['activity'];
                                        }
                                    }
                                    if (!empty($sharingTypes)) {
                                        $sharing = implode(', ', $sharingTypes);
                                    }
                                }

                                // Get destination name
                                $destination = '';
                                if ($record->package && $record->package->destination) {
                                    $destination = $record->package->title;
                                } else {
                                    $destination = $record->title ?? '';
                                }

                                $members = InfoGet::where('booking_id', $record->booking_id)
                                    ->orderBy('sharing_type')
                                    ->orderBy('member_number')
                                    ->get();

                                $paymentLink = PaymentLink::where('booking_id', $record->id)
                                    ->orWhere('booking_id', $record->booking_id)
                                    ->first();

                                $maleCount = InfoGet::where('booking_id', $record->id)->where('gender', 'Male')->count();
                                $femaleCount = InfoGet::where('booking_id', $record->id)->where('gender', 'Female')->count();

                                $totalBeforeDiscount = ($record->final_amount ?? 0) + ($record->total_coupon_discount ?? 0);
                                $discount = $record->total_coupon_discount ?? 0;

                                // Get coupons applied
                                $coupons = 'N/A';
                                if ($record->applied_coupons) {
                                    if (is_array($record->applied_coupons)) {
                                        // Check if array has 'code' key or simple array
                                        if (isset($record->applied_coupons[0]['code'])) {
                                            $coupons = implode(', ', array_column($record->applied_coupons, 'code'));
                                        } else {
                                            // Simple array like ["code1", "code2"]
                                            $coupons = implode(', ', $record->applied_coupons);
                                        }
                                    } elseif (is_string($record->applied_coupons)) {
                                        $coupons = $record->applied_coupons;
                                    }
                                }

                                if ($members->isEmpty()) {
                                    fputcsv($handle, [
                                        $sno++,
                                        $record->booking_id,
                                        $record->created_at ? Carbon::parse($record->created_at)->format('d-m-Y') : '',
                                        $destination,
                                        $record->start_date ? Carbon::parse($record->start_date)->format('d-m-Y') : '',
                                        $record->full_name,
                                        $record->phone,
                                        self::getTotalPersons($record),
                                        $maleCount,
                                        $femaleCount,
                                        $sharing,
                                        $record->booking_type,
                                        $record->payment_mode == 'online' ? 'Online' : ($record->payment_mode == 'cash' ? 'Manual' : $record->payment_mode),
                                        $record->assignedUser?->name ?? ($record->assign_to ?? ''),
                                        number_format($totalBeforeDiscount, 2),
                                        number_format($discount, 2),
                                        $coupons,
                                        number_format($discount, 2),
                                        number_format($record->final_amount ?? 0, 2),
                                        number_format($record->paid_amount ?? 0, 2),
                                        $paymentLink?->payment_link ?? '',
                                        number_format($record->due_amount ?? 0, 2),
                                        self::getPaymentLinkStatus($record),
                                        $record->due_amount > 0 ? 'Pending' : 'Paid'
                                    ]);
                                } else {
                                    $groupedMembers = $members->groupBy('sharing_type');

                                    foreach ($groupedMembers as $sharingType => $sharingGroup) {
                                        $sharingDisplay = ucfirst(str_replace('_', ' ', $sharingType));

                                        foreach ($sharingGroup as $member) {
                                            fputcsv($handle, [
                                                $sno++,
                                                $record->booking_id,
                                                $record->created_at ? Carbon::parse($record->created_at)->format('d-m-Y') : '',
                                                $destination,
                                                $record->start_date ? Carbon::parse($record->start_date)->format('d-m-Y') : '',
                                                $member->name ?? $record->full_name,
                                                $member->contact ?? $record->phone,
                                                1,
                                                $member->gender == 'Male' ? 1 : 0,
                                                $member->gender == 'Female' ? 1 : 0,
                                                $sharingDisplay,
                                                $record->payment_mode == 'online' ? 'Online' : ($record->payment_mode == 'cash' ? 'Manual' : $record->payment_mode),
                                                $record->assignedUser?->name ?? ($record->assign_to ?? ''),
                                                number_format($totalBeforeDiscount, 2),
                                                number_format($discount, 2),
                                                $coupons,
                                                number_format($discount, 2),
                                                number_format($record->final_amount ?? 0, 2),
                                                number_format($record->paid_amount ?? 0, 2),
                                                $paymentLink?->payment_link ?? '',
                                                number_format($record->due_amount ?? 0, 2),
                                                self::getPaymentLinkStatus($record),
                                                $record->due_amount > 0 ? 'Pending' : 'Paid'
                                            ]);
                                        }
                                    }
                                }
                            }

                            fclose($handle);
                        }, 'bookings-export-' . now()->format('Y-m-d-His') . '.csv');
                    }),

                Action::make('export_with_members')
                    ->label('Export With Members')
                    ->color('success')
                    ->icon('heroicon-o-users')
                    ->action(function ($livewire) {

                        $query = Bookings::query()->orderBy('id', 'desc');
                        $query->where('status', '!=', 'pending');
                        // ✅ Get filters
                        $filters = $livewire->tableFilters;

                        // ✅ Get search query
                        $search = $livewire->tableSearch;

                        // ✅ Apply search if present
                        if (!empty($search)) {
                            $query->where(function ($q) use ($search) {
                                $q->where('booking_id', 'like', "%{$search}%")
                                    ->orWhere('full_name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->orWhere('package_title', 'like', "%{$search}%");
                            });
                        }

                        // ✅ Apply all filters
                        if (!empty($filters['package_id']['value'])) {
                            $query->where('package_id', $filters['package_id']['value']);
                        }
                        if (!empty($filters['assign_to']['value'])) {
                            $query->where('assign_to', $filters['assign_to']['value']);
                        }
                        if (!empty($filters['payment_link_status']['value'])) {
                            $status = $filters['payment_link_status']['value'];
                            if ($status === 'no_link') {
                                $query->whereDoesntHave('paymentLinks');
                            } else {
                                $query->whereHas('paymentLinks', function ($q) use ($status) {
                                    $q->where('status', $status);
                                });
                            }
                        }
                        if (!empty($filters['start_date']['start_date'])) {
                            $query->whereDate('start_date', $filters['start_date']['start_date']);
                        }
                        if (!empty($filters['created_date']['created_date'])) {
                            $query->whereDate('created_at', $filters['created_date']['created_date']);
                        }
                        if (!empty($filters['status']['value'])) {
                            $query->where('status', $filters['status']['value']);
                        }
                        if (!empty($filters['payment_mode']['value'])) {
                            $query->where('payment_mode', $filters['payment_mode']['value']);
                        }
                        if (!empty($filters['amount_range']['min'])) {
                            $query->where('final_amount', '>=', $filters['amount_range']['min']);
                        }
                        if (!empty($filters['amount_range']['max'])) {
                            $query->where('final_amount', '<=', $filters['amount_range']['max']);
                        }
                        if (!empty($filters['due_amount']['value'])) {
                            if ($filters['due_amount']['value'] === true) {
                                $query->where('due_amount', '>', 0);
                            } elseif ($filters['due_amount']['value'] === false) {
                                $query->where('due_amount', 0);
                            }
                        }

                        $records = $query->get();

                        return response()->streamDownload(function () use ($records) {
                            $handle = fopen('php://output', 'w');
                            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                            // Headers
                            fputcsv($handle, [
                                'Bookid',
                                'Bookid date',
                                'Package',
                                'Dep date',
                                'Name',
                                'Contact no',
                                'm/f',
                                'Sharing',
                                'DOB',
                                'Age',
                                'Id (adhar/dl id)',
                                'Number',
                                'Emergency contact person',
                                'E. Contact Person Name',
                                'Relation with emergency',
                                'mode(manual/Online)',
                                'Sales person',
                                'Booking Type'
                            ]);

                            foreach ($records as $record) {
                                // Get destination
                                $destination = $record->package->title ?? $record->title ?? '';

                                // Get mode
                                $mode = $record->payment_mode == 'online' ? 'Online' : ($record->payment_mode == 'cash' ? 'Manual' : $record->payment_mode);

                                // Get sales person
                                $salesPerson = $record->assignedUser?->name ?? ($record->assign_to ?? '');

                                // Get booking type
                                $bookingType = $record->booking_type ?? '';

                                // ✅ Get members from InfoGet table sorted by priority (1 highest)
                                $members = InfoGet::where('booking_id', $record->id)
                                    ->orderByRaw('CASE 
                        WHEN priority = 1 THEN 1 
                        WHEN priority = 0 THEN 999 
                        ELSE priority 
                    END ASC')
                                    ->orderBy('member_number', 'asc')
                                    ->get();

                                // Get sharing from active_cost (for booking level fallback)
                                $bookingSharing = 'N/A';
                                if ($record->active_cost && is_array($record->active_cost)) {
                                    $sharingTypes = [];
                                    foreach ($record->active_cost as $costItem) {
                                        if (isset($costItem['activity'])) {
                                            $activity = $costItem['activity'];
                                            if (str_contains($activity, 'Double')) {
                                                $sharingTypes[] = 'Double Sharing';
                                            } elseif (str_contains($activity, 'Quad')) {
                                                $sharingTypes[] = 'Quad Sharing';
                                            }
                                        }
                                    }
                                    if (!empty($sharingTypes)) {
                                        $bookingSharing = implode(' + ', $sharingTypes);
                                    }
                                }

                                if ($members->isEmpty()) {
                                    // No members - export single row with booking data only
                                    fputcsv($handle, [
                                        $record->booking_id,
                                        $record->created_at ? Carbon::parse($record->created_at)->format('d-m-Y') : '',
                                        $destination,
                                        $record->start_date ? Carbon::parse($record->start_date)->format('d-m-Y') : '',
                                        $record->full_name,
                                        $record->phone,
                                        '',
                                        $bookingSharing,
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        $mode,
                                        $salesPerson,
                                        $bookingType
                                    ]);
                                } else {
                                    // ✅ Export each member with priority indicator (P) for priority 1
                                    foreach ($members as $member) {
                                        // Calculate age from DOB
                                        $age = '';
                                        if ($member->dob) {
                                            try {
                                                $age = Carbon::parse($member->dob)->age;
                                            } catch (\Exception $e) {
                                                $age = '';
                                            }
                                        }

                                        // Format DOB
                                        $dob = '';
                                        if ($member->dob) {
                                            try {
                                                $dob = Carbon::parse($member->dob)->format('d-m-Y');
                                            } catch (\Exception $e) {
                                                $dob = $member->dob;
                                            }
                                        }

                                        // Get sharing type (member level or booking level)
                                        $sharing = $member->sharing_type ? ucfirst(str_replace('_', ' ', $member->sharing_type)) : $bookingSharing;

                                        // ✅ Add (P) suffix if priority is 1
                                        $memberName = $member->name ?? $record->full_name;
                                        if ($member->priority == 1) {
                                            $memberName = $memberName . ' (P)';
                                        }

                                        fputcsv($handle, [
                                            $record->booking_id,
                                            $record->created_at ? Carbon::parse($record->created_at)->format('d-m-Y') : '',
                                            $destination,
                                            $record->start_date ? Carbon::parse($record->start_date)->format('d-m-Y') : '',
                                            $memberName,  // ✅ Name with (P) if priority is 1
                                            $member->contact ?? $record->phone,
                                            $member->gender == 'Male' ? 'M' : ($member->gender == 'Female' ? 'F' : ''),
                                            $sharing,
                                            $dob,
                                            $age,
                                            $member->id_proof_type ?? '',
                                            $member->id_proof_number ?? '',
                                            $member->emergency_name ?? '',
                                            $member->emergency_contact ?? '',
                                            $member->emergency_relation ?? '',
                                            $mode,
                                            $salesPerson,
                                            $bookingType
                                        ]);
                                    }
                                }
                            }

                            fclose($handle);
                        }, 'members-export-' . now()->format('Y-m-d-His') . '.csv');
                    }),

                Action::make('add_booking_header')
                    ->label('Add New Booking')
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->url(BookingsResource::getUrl('create'))
                    ->openUrlInNewTab(false),
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->extremePaginationLinks();
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

