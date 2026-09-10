<?php

namespace App\Filament\Resources\BookingsResource\Pages;

use App\Filament\Resources\BookingsResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Bookings;
use App\Models\Packages;
use App\Models\Coupon;
use App\Models\PaymentLink;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmation;
use Filament\Notifications\Notification;
use Razorpay\Api\Api;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateBookings extends CreateRecord
{
    protected static string $resource = BookingsResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $datass = Auth::user();
        $employee_id = $datass->id;
        $status = 'pending';
        $paidAmount = 0;
        $paymentType = null;

        // ===========================
        // ✅ ROUND OFF - FLOOR for all amounts
        // ===========================

        // Round final_amount
        if (isset($data['final_amount'])) {
            $data['final_amount'] = (int) floor((float) $data['final_amount']);
        }

        // Round paid_amount
        if (isset($data['paid_amount'])) {
            $data['paid_amount'] = (int) floor((float) $data['paid_amount']);
        }

        // Round coupon discount
        if (isset($data['total_coupon_discount'])) {
            $data['total_coupon_discount'] = (int) floor((float) $data['total_coupon_discount']);
        }

        // Round all transaction amounts
        if (!empty($data['payment_transactions']) && is_array($data['payment_transactions'])) {
            foreach ($data['payment_transactions'] as $key => $transaction) {
                if (isset($transaction['amount'])) {
                    $data['payment_transactions'][$key]['amount'] = (int) floor((float) $transaction['amount']);
                }
            }
        }

        // Round all active_cost amounts
        if (!empty($data['active_cost']) && is_array($data['active_cost'])) {
            foreach ($data['active_cost'] as $key => $activity) {
                if (isset($activity['cost'])) {
                    $data['active_cost'][$key]['cost'] = (int) floor((float) $activity['cost']);
                }
                if (isset($activity['discount_amount'])) {
                    $data['active_cost'][$key]['discount_amount'] = (int) floor((float) $activity['discount_amount']);
                }
                if (isset($activity['total_with_discount'])) {
                    $data['active_cost'][$key]['total_with_discount'] = (int) floor((float) $activity['total_with_discount']);
                }
                if (isset($activity['total_with_discount_and_gst'])) {
                    $data['active_cost'][$key]['total_with_discount_and_gst'] = (int) floor((float) $activity['total_with_discount_and_gst']);
                }
                if (isset($activity['quantity'])) {
                    $data['active_cost'][$key]['quantity'] = (int) $activity['quantity'];
                }
                if (isset($activity['gst_percent'])) {
                    $data['active_cost'][$key]['gst_percent'] = (int) $activity['gst_percent'];
                }
            }
        }

        // Verify payment with Razorpay
        if (!empty($data['payment_id'])) {
            try {
                $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
                $payment = $api->payment->fetch($data['payment_id']);

                if ($payment->status === 'captured') {
                    $status = 'confirmed';
                    $paidAmount = (int) floor((float) ($payment->amount / 100));

                    $finalAmount = (float) $data['final_amount'];

                    if ($paidAmount >= $finalAmount) {
                        $paymentType = 'full';
                    } elseif ($paidAmount >= ($finalAmount / 2)) {
                        $paymentType = 'half';
                    } else {
                        $paymentType = 'partial';
                    }
                } else {
                    $status = 'cancelled';
                }
            } catch (\Exception $e) {
                \Log::error('Payment verification failed: ' . $e->getMessage());
                $status = 'cancelled';
            }
        } else {
            // For manual bookings without Razorpay payment_id
            if (!empty($data['payment_transactions']) && is_array($data['payment_transactions'])) {
                $totalPaid = 0;
                foreach ($data['payment_transactions'] as $transaction) {
                    if (isset($transaction['amount'])) {
                        $totalPaid += (int) floor((float) $transaction['amount']);
                    }
                }
                $paidAmount = $totalPaid;
                $finalAmount = (float) $data['final_amount'];

                if ($paidAmount >= $finalAmount) {
                    $paymentType = 'full';
                    $status = 'confirmed';
                } elseif ($paidAmount >= ($finalAmount / 2)) {
                    $paymentType = 'half';
                    $status = 'confirmed';
                } elseif ($paidAmount > 0) {
                    $paymentType = 'partial';
                    $status = 'pending';
                } else {
                    $status = 'pending';
                }
            } elseif (isset($data['status'])) {
                $status = $data['status'];
                $paidAmount = (int) floor((float) ($data['paid_amount'] ?? 0));
                $paymentType = $data['payment_type'] ?? null;
            } else {
                $status = 'pending';
            }
        }

        // Get package with destination
        $package = Packages::with('destination')->find($data['package_id']);

        if (!$package) {
            Notification::make()
                ->title('Package Not Found')
                ->danger()
                ->send();
            throw new \Exception('Package not found');
        }

        // ===========================
        // ✅ BOOKING ID GENERATION
        // ===========================

        $stateCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', optional($package->destination)->state_code ?? 'XX'));
        $packageCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $package->package_code ?? $package->title ?? 'PKG'), 0, 3));
        $dateCode = now()->format('my');
        $prefix = "ETIN" . $stateCode . $packageCode . '-' . $dateCode;

        $currentMonth = now()->month;
        $currentYear = now()->year;

        $sequence = DB::table('booking_sequences')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->first();

        if ($sequence) {
            $last_number = $sequence->last_number + 1;
            DB::table('booking_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $last_number,
                    'updated_at' => now()
                ]);
        } else {
            $last_number = 1;
            DB::table('booking_sequences')->insert([
                'last_number' => $last_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $serial = str_pad($last_number, 3, '0', STR_PAD_LEFT);
        $bookingId = $prefix . $serial;

        // Payment history
        $paymentHistory = [];
        if ($paidAmount > 0) {
            $paymentHistory = [
                [
                    'amount' => $paidAmount,
                    'pay_method' => 'Razorpay',
                    'pay_type' => ucfirst($paymentType ?? 'partial'),
                    'date' => now()->format('Y-m-d'),
                    'time' => now()->format('H:i:s'),
                ]
            ];
        }

        $finalAmount = (int) floor((float) $data['final_amount']);
        $dueAmount = (int) floor($finalAmount - $paidAmount);

        // Payment Transactions
        $paymentTransactions = null;
        if (!empty($data['payment_transactions']) && is_array($data['payment_transactions'])) {
            $paymentTransactions = $data['payment_transactions'];
        }

        // Active Cost
        $activeCost = [];
        if (!empty($data['active_cost']) && is_array($data['active_cost'])) {
            $activeCost = $data['active_cost'];
        }

        // Coupon Data
        $appliedCoupons = [];
        if (!empty($data['applied_coupons'])) {
            if (is_array($data['applied_coupons'])) {
                $appliedCoupons = $data['applied_coupons'];
            } elseif (is_string($data['applied_coupons']) && !empty($data['applied_coupons'])) {
                $appliedCoupons = array_map('trim', explode(',', $data['applied_coupons']));
            }
        }

        if (!empty($appliedCoupons)) {
            foreach ($appliedCoupons as $couponCode) {
                Coupon::where('code', $couponCode)
                    ->where('is_used', false)
                    ->update(['is_used' => 1]);
            }
        }

        $totalCouponDiscount = (int) floor((float) ($data['total_coupon_discount'] ?? 0));

        // Create booking
        $booking = Bookings::create([
            'booking_token' => Str::uuid(),
            'booking_id' => $bookingId,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'package_id' => $data['package_id'],
            'package_title' => $data['package_title'] ?? $package->title,
            'duration' => $data['duration'] ?? $package->duration,
            'pickup' => $data['pickup'] ?? $package->pickup,
            'drop' => $data['drop'] ?? $package->drop,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'source' => 'dashboard',
            'assign_to' => $employee_id,
            'active_cost' => $activeCost,
            'payment_mode' => $data['payment_mode'] ?? 'online',
            'payment_id' => $data['payment_id'] ?? null,
            'payment_type' => $paymentType,
            'final_amount' => $finalAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'status' => $status,
            'payment_history' => $paymentHistory,
            'payment_transactions' => $paymentTransactions,
            'applied_coupons' => $appliedCoupons,
            'total_coupon_discount' => $totalCouponDiscount,
        ]);

        // ===========================
        // ✅ CREATE PAYMENT LINK FOR BALANCE AMOUNT
        // ===========================
        if ($dueAmount > 0) {
            try {
                $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

                $razorpayPaymentLink = $api->paymentLink->create([
                    'amount' => $dueAmount * 100,
                    'currency' => 'INR',
                    'accept_partial' => false,
                    'description' => "Balance payment for booking {$booking->booking_id}",
                    'customer' => [
                        'name' => $booking->full_name,
                        'email' => $booking->email,
                        'contact' => $booking->phone
                    ],
                    'notify' => [
                        'sms' => false,
                        'email' => false
                    ],
                    'reminder_enable' => true,
                    'notes' => [
                        'booking_id' => $booking->booking_id,
                        'payment_type' => 'balance'
                    ],
                    'callback_url' => 'https://tripogramclub.com/payment-callback',
                    'callback_method' => 'get'
                ]);

                $startDate = Carbon::parse($booking->start_date);
                $findpackage = Packages::where('id', $booking->package_id)->first();
                $days = $findpackage->day ?? 0;
                $expireDate = $startDate->copy()->subDays($days);

                PaymentLink::create([
                    'booking_id' => $booking->id,
                    'booking_token' => $booking->booking_token,
                    'razorpay_link_id' => $razorpayPaymentLink->id,
                    'payment_link' => $razorpayPaymentLink->short_url,
                    'amount' => $dueAmount,
                    'expire_at' => $expireDate->format('Y-m-d'),
                    'status' => 'pending'
                ]);

                \Log::info('Payment Link Created for balance amount', [
                    'booking_id' => $booking->booking_id,
                    'amount' => $dueAmount,
                    'link' => $razorpayPaymentLink->short_url
                ]);

            } catch (\Exception $e) {
                \Log::error('Payment Link creation failed: ' . $e->getMessage());
            }
        }

        $notificationMessage = 'Booking Created Successfully!';
        if ($dueAmount > 0 && $status === 'confirmed') {
            $notificationMessage = 'Booking Created! Payment link sent to customer for balance amount: ₹' . number_format($dueAmount, 2);
        }

        Notification::make()
            ->title($notificationMessage)
            ->success()
            ->send();

        return $booking;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}