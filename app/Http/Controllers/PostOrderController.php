<?php

namespace App\Http\Controllers;

use App\Models\Bookings;
use App\Models\Packages;
use App\Models\Coupon;
use App\Models\PaymentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Razorpay\Api\Api;
use App\Models\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmedMail;
use App\Models\WhatsAppQueue;
use App\Models\EmailQueue;
use App\Models\Email;
use Illuminate\Support\Facades\Log; // ✅ Ye line add karo

class PostOrderController extends Controller
{
    protected $razorpay_key;
    protected $razorpay_secret;

    public function __construct()
    {
        $setting = Settings::first();
        $this->razorpay_key = $setting->razorpay_key_id;
        $this->razorpay_secret = $setting->razorpay_key_secret;
    }

    /**
     * STEP 1: Sirf booking create karo
     */
    public function emailwhatsapp(Request $request)
    {
        $bookings = EmailQueue::where('status', 'pending')->get();
        $results = [];
        $ccEmails = DB::table('emails')
            ->where('status', 1)
            ->pluck('email')
            ->toArray();


        foreach ($bookings as $booking) {
            try {
                // Check if booking exists
                $bookingss = Bookings::where('id', $booking->booking_id)->first();
                $ccEmails[] = $bookingss->email;
                if (!$bookingss) {
                    $booking->update([
                        'status' => 'failed',
                        'error_message' => 'Booking not found for ID: ' . $booking->booking_id
                    ]);

                    $results[] = [
                        'success' => false,
                        'booking_id' => $booking->booking_id,
                        'error' => 'Booking not found'
                    ];
                    continue;
                }

                // Get package with destination
                $package = Packages::with('destination')->find($bookingss->package_id);

                if (!$package) {
                    $booking->update([
                        'status' => 'failed',
                        'error_message' => 'Package not found'
                    ]);
                    continue;
                }

                // ✅ Send Email only

                Mail::to($ccEmails)->send(
                    new BookingConfirmedMail($bookingss, $package->destination, $package->itinerary_pdf, $ccEmails)
                );

                // ✅ Update status
                $booking->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);

                $results[] = [
                    'success' => true,
                    'booking_id' => $booking->booking_id,
                    'email' => $booking->email
                ];

                usleep(500000);

            } catch (\Exception $e) {
                \Log::error('Email failed: ' . $e->getMessage());

                $booking->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);

                $results[] = [
                    'success' => false,
                    'booking_id' => $booking->booking_id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ]);
    }
    // 	public function whatsapphook(Request $request)
// {
//     $bookings = WhatsAppQueue::where('status', 'pending')->get();

    //     $results = [];

    //     foreach($bookings as $booking) {
//         // Check if booking exists in Bookings table
//         $bookingss = Bookings::where('id', $booking->booking_id)->first();

    //         if (!$bookingss) {
//             $booking->update([
//                 'status' => 'failed',
//                 'error_message' => 'Booking not found in Bookings table for ID: ' . $booking->booking_id
//             ]);

    //             $results[] = [
//                 'success' => false,
//                 'booking_id' => $booking->booking_id,
//                 'error' => 'Booking not found'
//             ];
//             continue;
//         }

    //         // ✅ This will now return an array
//         $result = $this->sendWhatsAppAgain($bookingss);
//         $results[] = $result;

    //         // Update queue status
//         if ($result['success']) {
//             $booking->update(['status' => 'sent']);
//         } else {
//             $booking->update([
//                 'status' => 'failed',
//                 'error_message' => $result['error'] ?? 'Unknown error'
//             ]);
//         }

    //         // Optional: Add a small delay to avoid rate limiting
//         usleep(500000);
//     }

    //     return response()->json([
//         'success' => true,
//         'processed' => count($results),
//         'results' => $results
//     ]);
// }

    /**
     * Send WhatsApp message for a specific booking
     * ✅ Returns array with success/error status
     */
    /**
     * Send WhatsApp message for a specific booking
     * ✅ Returns array with success/error status
     */
    // private static function sendWhatsAppAgain($booking)
// {
//     try {
//         // Validation at start
//         if (!$booking) {
//             return [
//                 'success' => false,
//                 'error' => 'Booking object is null'
//             ];
//         }

    //         \Log::info('WhatsApp API START (Send Again Button)', [
//             'booking_id' => $booking->booking_id ?? 'N/A',
//             'phone' => $booking->phone ?? 'N/A'
//         ]);

    //         $bookingsssss = $booking->created_at?->format('d.m.Y') ?? date('d.m.Y');
//         $booking_dataas =  ($booking->booking_id ?? 'N/A') . ', booking_date:' . $bookingsssss;

    //         // Check if package exists
//         $package = Packages::where('id', $booking->package_id)->first();
//         if (!$package) {
//             return [
//                 'success' => false,
//                 'booking_id' => $booking->booking_id,
//                 'error' => 'Package not found for ID: ' . $booking->package_id
//             ];
//         }

    //         // Handle null active_cost
//         $activeCostArray = $booking->active_cost;
//         if (is_null($activeCostArray)) {
//             $activeCostArray = [];
//         } elseif (is_string($activeCostArray)) {
//             $activeCostArray = json_decode($activeCostArray, true);
//             if (!is_array($activeCostArray)) {
//                 $activeCostArray = [];
//             }
//         }

    //         // Calculate totals
//         $gstTotal = 0;
//         $subtotalWithoutGST = 0;
//         $activeCostData = '';
//         $hasGST = false;  // ✅ Check if any item has GST

    //         if (!empty($activeCostArray) && is_array($activeCostArray)) {
//             $items = [];
//             foreach ($activeCostArray as $item) {
//                 $activityName = $item['activity'] ?? 'Unknown';
//                 $cost = (float)($item['cost'] ?? 0);
//                 $quantity = (float)($item['quantity'] ?? 1);
//                 $totalWithDiscount = (float)($item['total_with_discount'] ?? 0);
//                 $totalWithGST = (float)($item['total_with_discount_and_gst'] ?? 0);
//                 $gstPercent = (float)($item['gst_percent'] ?? 0);

    //                 $gstAmount = $totalWithGST - $totalWithDiscount;
//                 $gstTotal += $gstAmount;
//                 $subtotalWithoutGST += $totalWithDiscount;

    //                 // ✅ Check if GST exists (>0)
//                 if ($gstPercent > 0) {
//                     $hasGST = true;
//                 }

    //                 $items[] = "{$activityName}: ₹" . number_format($cost, 2) . " × {$quantity} = ₹" . number_format($totalWithDiscount, 2);
//             }
//             $activeCostData = implode(' | ', $items);
//         } else {
//             $activeCostData = 'No activities selected';
//         }

    //         // Format start date
//         $startDate = 'Date not set';
//         if ($booking->start_date) {
//             $dateStr = (string)$booking->start_date;
//             if (!str_contains($dateStr, '1969') && !str_contains($dateStr, '1970-01-01')) {
//                 try {
//                     $startDate = Carbon::parse($booking->start_date)
//                         ->timezone('Asia/Kolkata')
//                         ->format('d M Y');
//                 } catch (\Exception $e) {
//                     $startDate = 'Date not set';
//                 }
//             }
//         }

    //         // Check phone number
//         if (empty($booking->phone)) {
//             return [
//                 'success' => false,
//                 'booking_id' => $booking->booking_id,
//                 'error' => 'Phone number is empty'
//             ];
//         }

    //         // ✅ Prepare text7 with or without GST
//         $text7 = "Subtotal: ₹" . number_format($subtotalWithoutGST, 2);

    //         if ($hasGST && $gstTotal > 0) {
//             // Agar GST hai toh dikhao
//             $text7 .= " | Total GST: ₹" . number_format($gstTotal, 2) . " (5%)";
//         }

    //         $text7 .= " | Grand Total: ₹" . number_format($booking->final_amount ?? 0, 2);

    //         // ✅ WhatsApp API call
//         $response = Http::timeout(30)->post('https://api.sendinai.com/sender', [
//             "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
//             "phone" => $booking->phone,
//             "template_name" => "booking_confirmation_enlivetrips",
//             "template_language" => "EN_US",
//             "text1" => $booking->full_name ?? 'Guest',
//             "text2" => "https://www.enlivetrips.com/booking-detail?id=" . ($booking->booking_token ?? ''),
//             "text3" => $booking_dataas,
//             "text4" => $package->title ?? 'Package',
//             "text5" => $startDate,
//             "text6" => $activeCostData,
//             "text7" => $text7,
//             "text8" => "Paid: ₹" . number_format($booking->paid_amount ?? 0, 2),
//             "text9" => "Due: ₹" . number_format($booking->due_amount ?? 0, 2),
//             "text10" => "https://www.enlivetrips.com/terms-and-conditions"
//         ]);

    //         \Log::info('WhatsApp API RESPONSE (Send Again Button)', [
//             'status' => $response->status(),
//             'body' => $response->body(),
//             'booking_id' => $booking->booking_id
//         ]);

    //         if ($response->successful()) {
//             \Log::info('WhatsApp resent successfully for booking: ' . $booking->booking_id);

    //             return [
//                 'success' => true,
//                 'booking_id' => $booking->booking_id,
//                 'message' => 'WhatsApp sent successfully'
//             ];
//         } else {
//             return [
//                 'success' => false,
//                 'booking_id' => $booking->booking_id,
//                 'error' => 'API failed: ' . $response->body()
//             ];
//         }

    //     } catch (\Throwable $e) {
//         \Log::error('WhatsApp API FAILED (Send Again Button)', [
//             'error' => $e->getMessage(),
//             'booking_id' => $booking->booking_id ?? 'N/A',
//             'line' => $e->getLine(),
//             'file' => $e->getFile()
//         ]);

    //         return [
//             'success' => false,
//             'booking_id' => $booking->booking_id ?? 'N/A',
//             'error' => $e->getMessage()
//         ];
//     }
// }
    public function createBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|digits:10',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'package_id' => 'required|exists:packages,id',
            'payment_type' => 'required|in:half,full',
            'final_amount' => 'required|numeric|min:0',
            'active_cost' => 'nullable|array',
            'applied_coupons' => 'nullable|array',
            'special_note' => 'nullable',
            'total_coupon_discount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $bookingId = $this->generateBookingId($request->package_id);
            $validCoupons = $this->validateCoupons($request->applied_coupons ?? []);
            $package = Packages::with('destination')->find($request->package_id);

            $booking = Bookings::create([
                'booking_token' => Str::uuid(),
                'booking_id' => $bookingId,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'package_id' => $package->id,
                'package_title' => $package->title,
                'duration' => $package->duration,
                'pickup' => $package->pickup,
                'drop' => $package->drop,
                'source' => 'website',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'active_cost' => $request->active_cost,
                'payment_type' => $request->payment_type,
                'final_amount' => $request->final_amount,
                'paid_amount' => 0,
                'due_amount' => $request->final_amount,
                'status' => 'pending',
                'special_note' => $request->special_note,
                'payment_status' => 'pending',
                'applied_coupons' => $validCoupons,
                'total_coupon_discount' => $request->total_coupon_discount ?? 0,
                'payment_history' => [],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'booking_token' => $booking->booking_token,
                    'due_amount' => $booking->due_amount,
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * CREATE PAYMENT LINK FOR DUE AMOUNT
     */
    public function paymentlink($requestData, $booking)
    {
        $amount = $booking->due_amount;

        if ($amount <= 0) {
            return null;
        }

        $response = Http::withBasicAuth(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'))
            ->post('https://api.razorpay.com/v1/payment_links', [
                "amount" => $amount * 100,
                "currency" => "INR",
                "description" => "Due Payment for Booking ID: " . $booking->booking_id,
                "customer" => [
                    "name" => $booking->full_name,
                    "email" => $booking->email,
                    "contact" => $booking->phone
                ],
                "notes" => [
                    "booking_id" => $booking->booking_id,
                    "booking_token" => $booking->booking_token
                ],
                "notify" => [
                    "sms" => false,
                    "email" => false
                ]
            ]);

        if ($response->successful()) {
            $data = $response->json();

            $startDate = Carbon::parse($booking->start_date);
            $findpackage = Packages::where('id', $booking->package_id)->first();
            $days = $findpackage->day ?? 0;
            $expireDate = $startDate->copy()->subDays($days);

            PaymentLink::create([
                'booking_id' => $booking->id,
                'razorpay_link_id' => $data['id'],
                'payment_link' => $data['short_url'],
                'amount' => $amount,
                'expire_at' => $expireDate->format('Y-m-d'),
                'status' => 'pending',
            ]);

            return $data['short_url'];
        }

        return null;
    }

    /**
     * STEP 2: Payment verification with signature
     */
    public function verifyPayment($razorpayPaymentId)
    {
        try {
            $api = new Api($this->razorpay_key, $this->razorpay_secret);

            // Check if payment exists
            try {
                $payment = $api->payment->fetch($razorpayPaymentId);
            } catch (\Exception $e) {
                \Log::error('Razorpay payment fetch failed: ' . $e->getMessage());
                return [
                    'status' => 'failed',
                    'message' => 'Payment ID not found or invalid: ' . $e->getMessage()
                ];
            }

            // Log payment status for debugging
            \Log::info('Payment status: ' . $payment->status, [
                'payment_id' => $razorpayPaymentId,
                'status' => $payment->status
            ]);

            if ($payment->status === 'authorized') {
                $capturedPayment = $payment->capture([
                    'amount' => $payment->amount
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Payment captured successfully',
                    'payment' => $capturedPayment->toArray()
                ];
            }

            if ($payment->status === 'captured') {
                return [
                    'status' => 'success',
                    'message' => 'Payment already captured',
                    'payment' => $payment->toArray()
                ];
            }

            return [
                'status' => 'failed',
                'message' => "Payment not authorized or captured. Current status: {$payment->status}",
                'payment' => $payment->toArray()
            ];

        } catch (\Exception $e) {
            \Log::error('Payment verification exception: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update booking after payment verification
     */
    public function updatePayment(Request $request)
    {
        // Add validation rules
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'razorpay_payment_id' => 'required|string|max:255',
            'razorpay_order_id' => 'nullable|string|max:255',
            'paid_amount' => 'required|numeric|min:1',
            'payment_type' => 'required|in:half,full',
            'razorpay_signature' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Additional validation - Check if booking exists
        $booking = Bookings::find($request->booking_id);
        if ($booking->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Booking already paid',
                'paid_amount' => $booking->paid_amount
            ], 400);
        }
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Check if paid_amount exceeds final_amount
        $totalPaidAfterThis = $booking->paid_amount + $request->paid_amount;
        if ($totalPaidAfterThis > $booking->final_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Paid amount cannot exceed final amount',
                'data' => [
                    'final_amount' => $booking->final_amount,
                    'already_paid' => $booking->paid_amount,
                    'attempting_to_pay' => $request->paid_amount,
                    'max_allowed' => $booking->final_amount - $booking->paid_amount
                ]
            ], 422);
        }

        // Check if booking is already confirmed/completed
        if ($booking->status === 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already confirmed'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Verify payment with Razorpay
            $paymentResult = $this->verifyPayment($request->razorpay_payment_id);

            if ($paymentResult['status'] !== 'success') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed: ' . ($paymentResult['message'] ?? 'Unknown error'),
                    'payment_details' => $paymentResult['payment'] ?? null
                ], 400);
            }

            $newPaidAmount = $booking->paid_amount + $request->paid_amount;
            $remainingDue = $booking->final_amount - $newPaidAmount;

            if ($newPaidAmount >= $booking->final_amount) {
                $paymentStatus = 'paid';
                $bookingStatus = 'confirmed';
            } else {
                $paymentStatus = 'partial';
                $bookingStatus = 'confirmed';
            }

            $paymentHistory = $booking->payment_history ?? [];
            $paymentHistory[] = [
                'amount' => $request->paid_amount,
                'payment_id' => $request->razorpay_payment_id,
                'order_id' => $request->razorpay_order_id ?? null,
                'pay_method' => 'Razorpay',
                'pay_type' => $newPaidAmount >= $booking->final_amount ? 'Full Payment' : 'Partial Payment',
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'verified' => true
            ];

            $booking->paid_amount = $newPaidAmount;
            $booking->due_amount = $remainingDue;
            $booking->payment_status = $paymentStatus;
            $booking->status = $bookingStatus;
            $booking->payment_id = $request->razorpay_payment_id;

            // Store order_id if provided
            if ($request->razorpay_order_id) {
                $booking->razorpay_order_id = $request->razorpay_order_id;
            }

            $booking->payment_history = $paymentHistory;
            $booking->save();

            // Add to queues
            EmailQueue::create([
                'booking_id' => $booking->id,
                'status' => 'pending',
            ]);

            WhatsAppQueue::create([
                'booking_id' => $booking->id,
                'status' => 'pending',
            ]);

            // Generate payment link if partial payment
            if ($request->payment_type == 'half' && $remainingDue > 0) {
                $paymentLink = $this->paymentlink($request, $booking);
            }

            // Update coupons if fully paid
            if (!empty($booking->applied_coupons) && $bookingStatus === 'confirmed') {
                Coupon::whereIn('code', $booking->applied_coupons)
                    ->update(['is_used' => 1]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $bookingStatus === 'confirmed' ? 'Payment successful! Booking confirmed.' : 'Partial payment recorded successfully',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'booking_token' => $booking->booking_token,
                    'paid_amount' => $booking->paid_amount,
                    'due_amount' => $booking->due_amount,
                    'payment_status' => $booking->payment_status,
                    'status' => $booking->status,
                    'payment_id' => $booking->payment_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error for debugging
            \Log::error('Payment update failed: ' . $e->getMessage(), [
                'booking_id' => $request->booking_id,
                'payment_id' => $request->razorpay_payment_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateBookingId($packageId)
    {
        $package = Packages::with('destination')->find($packageId);
        $stateCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', optional($package->destination)->state_code ?? 'XX'));
        $packageCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $package->package_code ?? 'PKG'));
        $dateCode = Carbon::now()->format('my');
        $prefix = "ETIN{$stateCode}{$packageCode}-{$dateCode}";

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
                ->update(['last_number' => $last_number]);
        } else {
            $last_number = 1;
            DB::table('booking_sequences')->insert([
                'last_number' => $last_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $serial = str_pad($last_number, 3, '0', STR_PAD_LEFT);
        return $prefix . $serial;
    }

    private function validateCoupons($couponCodes)
    {
        if (empty($couponCodes)) {
            return [];
        }

        return Coupon::whereIn('code', $couponCodes)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where('is_used', 0)
            ->pluck('code')
            ->toArray();
    }
}