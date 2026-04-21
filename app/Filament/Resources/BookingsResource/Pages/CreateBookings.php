<?php

namespace App\Filament\Resources\BookingsResource\Pages;

use App\Filament\Resources\BookingsResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Bookings;
use App\Models\Packages;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmation;
use Filament\Notifications\Notification;
use Razorpay\Api\Api;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CreateBookings extends CreateRecord
{
    protected static string $resource = BookingsResource::class;

    protected function handleRecordCreation(array $data): Model
    {
      
   
        // Verify payment with Razorpay
        $status = 'pending';
        $paidAmount = 0;
        $paymentType = null;

        if (!empty($data['payment_id'])) {
            try {
                $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
                $payment = $api->payment->fetch($data['payment_id']);

                if ($payment->status === 'captured') {
                    $status = 'confirmed';
                    $paidAmount = (float)($payment->amount / 100);

                    $finalAmount = (float)$data['final_amount'];

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
            // Check if payment_transactions exist
            if (!empty($data['payment_transactions']) && is_array($data['payment_transactions'])) {
                $totalPaid = 0;
                foreach ($data['payment_transactions'] as $transaction) {
                    if (isset($transaction['amount'])) {
                        $totalPaid += (float)$transaction['amount'];
                    }
                }
                $paidAmount = $totalPaid;
                $finalAmount = (float)$data['final_amount'];
                
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
            } 
            // If status is manually selected in form
            elseif (isset($data['status'])) {
                $status = $data['status'];
                $paidAmount = (float)($data['paid_amount'] ?? 0);
                $paymentType = $data['payment_type'] ?? null;
            } else {
                $status = 'pending';
            }
        }

        // Get package
        $package = Packages::find($data['package_id']);

        // Generate booking ID
        $pickup = strtoupper(substr($package->pickup, 0, 2));

        $titleWords = explode(' ', $package->title);
        $titleInitials = '';

        foreach ($titleWords as $word) {
            if (!empty($word)) {
                $titleInitials .= strtoupper(substr($word, 0, 1));
            }
        }

        $titleInitials = substr($titleInitials, 0, 3);
        $dateCode = now()->format('my');

        $prefix = "ETIN{$pickup}{$titleInitials}-{$dateCode}";

        $lastBooking = Bookings::where('booking_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = intval(substr($lastBooking->booking_id, -3));
            $serial = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $serial = '001';
        }

        $bookingId = $prefix . $serial;

        // Payment history
        $paymentHistory = [];
        if ($paidAmount > 0) {
            $paymentHistory = [[
                'amount' => $paidAmount,
                'pay_method' => 'Razorpay',
                'pay_type' => ucfirst($paymentType ?? 'partial'),
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
            ]];
        }

        $finalAmount = (float)$data['final_amount'];
        $dueAmount = $finalAmount - $paidAmount;

        // ✅ Payment Transactions (ARRAY ONLY)
        $paymentTransactions = null;

        if (!empty($data['payment_transactions']) && is_array($data['payment_transactions'])) {
            $paymentTransactions = $data['payment_transactions'];
        }

        // ✅ Active Cost (ARRAY ONLY)
        $activeCost = [];

        if (!empty($data['active_cost']) && is_array($data['active_cost'])) {
            $activeCost = $data['active_cost'];
        }

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
        ]);

        // Send email and WhatsApp - Only if booking is confirmed
        if ($status === 'confirmed') {
            try {
                // Send Email
                Mail::to($booking->email)->send(new BookingConfirmation($booking));

                // Decode active_cost if it's a JSON string
                $activeCostArray = [];
                if (is_string($booking->active_cost)) {
                    $activeCostArray = json_decode($booking->active_cost, true);
                } elseif (is_array($booking->active_cost)) {
                    $activeCostArray = $booking->active_cost;
                }

                // Calculate totals
                $gstTotal = 0;
                $subtotalWithoutGST = 0;
                $activeCostData = '';

                if (!empty($activeCostArray) && is_array($activeCostArray)) {
                    $items = [];
                    foreach ($activeCostArray as $item) {
                        $activityName = $item['activity'] ?? 'Unknown';
                        $cost = (float)($item['cost'] ?? 0);
                        $quantity = (float)($item['quantity'] ?? 1);
                        $totalWithDiscount = (float)($item['total_with_discount'] ?? 0);
                        $totalWithGST = (float)($item['total_with_discount_and_gst'] ?? 0);
                        
                        $gstAmount = $totalWithGST - $totalWithDiscount;
                        $gstTotal += $gstAmount;
                        $subtotalWithoutGST += $totalWithDiscount;
                        
                        $items[] = "{$activityName}: ₹" . number_format($cost, 2) . " × {$quantity} = ₹" . number_format($totalWithDiscount, 2);
                    }
                    $activeCostData = implode(' | ', $items);
                } else {
                    $activeCostData = 'No activities selected';
                }

                // Format start date - Simple fix for 1969/1970 issue
                $startDate = 'Date not set';
                if ($booking->start_date) {
                    $dateStr = (string)$booking->start_date;
                    // Check if it's a valid date (not 1969 or 1970)
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

                // Send WhatsApp
                \Log::info('WhatsApp API START (Filament Create)', [
                    'booking_id' => $booking->booking_id,
                    'phone' => $booking->phone,
                    'status' => $status
                ]);

                $response = Http::post('https://api.sendinai.com/sender', [
                    "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
                    "phone" => $booking->phone,
                    "template_name" => "booking_confirmation_enlivetrips",
                    "template_language" => "EN_US",
                    "text1" => $booking->full_name,
                    "text2" => "https://www.enlivetrips.com/booking-detail?id=" . $booking->booking_token,
                    "text3" => $booking->booking_id,
                    "text4" => $package->title,
                    "text5" => $startDate,
                    "text6" => $activeCostData,
                    "text7" => "Subtotal: ₹" . number_format($subtotalWithoutGST, 2) . " | Total GST: ₹" . number_format($gstTotal, 2) . " (5%) | Grand Total: ₹" . number_format($booking->final_amount, 2),
                    "text8" => "Paid: ₹" . number_format($booking->paid_amount, 2),
                    "text9" => "Due: ₹" . number_format($booking->due_amount, 2),
                    "text10" => "https://www.enlivetrips.com/terms"
                ]);

                \Log::info('WhatsApp API RESPONSE (Filament)', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                if ($response->successful()) {
                    \Log::info('WhatsApp sent successfully for booking: ' . $booking->booking_id);
                } else {
                    \Log::error('WhatsApp failed for booking: ' . $booking->booking_id . ' - ' . $response->body());
                }

            } catch (\Exception $e) {
                \Log::error('Mail/WhatsApp error: ' . $e->getMessage());
            }
        } else {
            \Log::info('Booking status is ' . $status . ', skipping WhatsApp notification for booking: ' . $booking->booking_id);
        }

        Notification::make()
            ->title('Booking Created')
            ->success()
            ->send();

        return $booking;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}