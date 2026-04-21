<?php

namespace App\Http\Controllers;

use App\Models\ActiveCosts;
use App\Models\Bookings;
use App\Models\PackageDates;
use App\Models\Packages;
use App\Models\PopupForms;
use App\Models\Newsletters;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use App\Models\Settings;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Str;
// use App\Models\Bookings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\InfoGet;
class BookingController extends Controller
{
    protected $razorpay_key;
    protected $razorpay_secret;

    public function __construct()
    {
        $setting = Settings::first();
        $this->razorpay_key = $setting->razorpay_key_id;
        $this->razorpay_secret = $setting->razorpay_key_secret;
    }
    
    
    
// public function bookinginformationupdate(Request $request) 
// {
//     // booking_id booking_info ke andar hai
//     $bookingId = data_get($request->all(), 'booking_info.booking_id');

//     $booking = Bookings::find($bookingId);

//     if (!$booking) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Booking not found'
//         ], 404);
//     }

//     // sharing_details ko save karna hai
//     $sharingDetails = data_get($request->all(), 'sharing_details');

//     // Yaha galti thi -> $booking->data_get ❌
//     // Sahi field name lagao (example: sharing_details)
//     $booking->data_get = $sharingDetails;

//     // ✅ booking_token ko null kar diya
//     $booking->booking_token = null;

//     $booking->save();

//     return response()->json([
//         'success' => true,
//         'message' => 'Booking information updated successfully',
//         'data' => $booking
//     ]);
// }


public function bookinginformationupdate(Request $request)
{
    $bookingId = data_get($request->all(), 'booking_info.booking_id');

    $booking = Bookings::find($bookingId);

    if (!$booking) {
        return response()->json([
            'success' => false,
            'message' => 'Booking not found'
        ], 404);
    }

    $sharingDetails = data_get($request->all(), 'sharing_details');

    if ($sharingDetails) {

        foreach ($sharingDetails as $sharingType => $details) {

            if (isset($details['members'])) {

                foreach ($details['members'] as $member) {

                    InfoGet::create([
                        'package_id' => $booking->package_id,
                        'booking_id' => $booking->id,
                        'start_date' => $booking->start_date,

                        'sharing_type' => $sharingType,
                        'member_number' => $member['member_number'] ?? null,

                        'name' => $member['name'] ?? null,
                        'email' => $member['email'] ?? null,
                        'contact' => $member['contact'] ?? null,
                        'gender' => $member['gender'] ?? null,
                        'dob' => $member['dob'] ?? null,

                        'id_proof_type' => $member['id_proof_type'] ?? null,
                        'id_proof_number' => $member['id_proof_number'] ?? null,

                        'emergency_name' => $member['emergency_name'] ?? null,
                        'emergency_contact' => $member['emergency_contact'] ?? null,
                        'emergency_relation' => $member['emergency_relation'] ?? null,

                        'has_file' => $member['has_file'] ?? false
                    ]);
                }
            }
        }
    }

    // booking table me json bhi save kar diya
    $booking->data_get = $sharingDetails;

    // token null
    $booking->booking_token = null;

   $datasss =  $booking->save();
    return $datasss;
    return response()->json([
        'success' => true,
        'message' => 'Booking information updated successfully'
    ]);
}
 public function get_booking(Request $request)
{
    $booking = Bookings::where('booking_token',$request->id)->first();

    if (!$booking) {
        return response()->json([
            'success' => false,
            'message' => 'Booking not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $booking
    ]);
}

    public function verifyPayment($razorpayPaymentId)
    {
        $api = new Api($this->razorpay_key, $this->razorpay_secret);

        try {
            $payment = $api->payment->fetch($razorpayPaymentId);

            // Agar payment authorized hai → capture karo
            if ($payment->status === 'authorized') {
                $capturedPayment = $payment->capture(['amount' => $payment->amount]);

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
                'message' => 'Payment not authorized or captured',
                'payment' => $payment->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
public function add_booking(Request $request)
{
    $validator = Validator::make($request->all(), [
        'full_name'   => 'required|string|max:255',
        'email'       => 'required|email|max:255',
        'phone'       => 'required|digits:10',
        'start_date'  => 'required|date',
        'end_date'    => 'nullable|date|after_or_equal:start_date',
        'active_cost' => 'nullable|array',
        'payment_type' => 'required|in:half,full',
        'final_amount' => 'required|numeric|min:0',
        'paid_amount'  => 'required|numeric|min:0',
        'razorpay_payment_id' => 'required|string',
        'package_id' => 'required|exists:packages,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    $status = 'confirmed';

    if (!$request->razorpay_payment_id) {
        $status = 'cancelled';
    } else {
        $paymentResult = $this->verifyPayment($request->razorpay_payment_id);

        if ($paymentResult['status'] !== 'success') {
            $status = 'cancelled';
        }
    }

    $package = Packages::with('destination')->find($request->input('package_id'));

    if (!$package) {
        return response()->json([
            'success' => false,
            'message' => 'Package not found'
        ], 404);
    }

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

    // SAFE ACTIVE COST HANDLING
    $activeCostArray = $request->active_cost;
    
    if (is_string($activeCostArray)) {
        $activeCostArray = json_decode($activeCostArray, true);
    }

    $gstTotal = 0;
    $subtotalWithoutGST = 0;
    $activeCostText = 'No activities selected';

    if (is_array($activeCostArray) && !empty($activeCostArray)) {
        $items = [];
        
        foreach ($activeCostArray as $item) {
            $activityName = $item['activity'] ?? 'Activity';
            $cost = (float)($item['cost'] ?? 0);
            $quantity = (float)($item['quantity'] ?? 1);
            
            $totalWithDiscount = (float)($item['total_with_discount'] ?? 0);
            $totalWithGST = (float)($item['total_with_discount_and_gst'] ?? $totalWithDiscount);
            
            $gstAmount = $totalWithGST - $totalWithDiscount;
            
            $gstTotal += $gstAmount;
            $subtotalWithoutGST += $totalWithDiscount;
            
            $items[] = "{$activityName}: ₹" . number_format($cost, 2) .
                " × {$quantity} = ₹" . number_format($totalWithDiscount, 2);
        }
        
        $activeCostText = implode(' | ', $items);
    }

    // SAFE DATE HANDLING
    $startDate = 'Date not set';
    if ($request->input('start_date')) {
        try {
            $startDate = Carbon::parse($request->input('start_date'))
                ->timezone('Asia/Kolkata')
                ->format('d M Y');
        } catch (\Exception $e) {
            $startDate = 'Date not set';
        }
    }

    $booking = Bookings::create([
        'booking_token' => Str::uuid(),
        'full_name'  => $request->input('full_name'),
        'email'      => $request->input('email'),
        'phone'      => $request->input('phone'),
        'package_id'    => $package->id,
        'booking_id'    => $bookingId,
        'package_title' => $package->title,
        'duration'      => $package->duration,
        'pickup'        => $package->pickup,
        'drop'          => $package->drop,
        'start_date' => $request->input('start_date'),
        'end_date'    => $request->input('end_date'),
        'active_cost'     => $request->active_cost,
        'payment_id' => $request->input('razorpay_payment_id'),
        'payment_mode' => 'online',
        'payment_type' => $request->input('payment_type'),
        'final_amount' => $request->input('final_amount'),
        'paid_amount'  => $request->input('paid_amount'),
        'due_amount'   => $request->input('final_amount') - $request->input('paid_amount'),
        'status' => $status,
        'payment_history' => [[
            'amount' => $request->input('paid_amount'),
            'pay_method' => 'Razorpay',
            'pay_type' => 'Advance Payment',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s')
        ]]
    ]);

    Mail::to($booking->email)->send(
        new BookingConfirmedMail($booking, $package->destination, $package->itinerary_pdf)
    );

    try {
        Http::post('https://api.sendinai.com/sender', [
            "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
            "phone" => $booking->phone ?? '7017026233',
            "template_name" => "booking_confirmation_enlivetrips",
            "template_language" => "EN_US",
            "text1" => $booking->full_name ?? 'User',
            "text2" => "https://www.enlivetrips.com/booking-detail?id=" . ($booking->booking_token ?? ''),
            "text3" => $booking->booking_id ?? 'N/A',
            "text4" => $package->title ?? 'Package',
            "text5" => $startDate,  // ✅ FIXED - removed invalid 28-08-2003
            "text6" => $activeCostText,
            "text7" => "Subtotal: ₹" . number_format($subtotalWithoutGST, 2) .
                " | Total GST: ₹" . number_format($gstTotal, 2) .
                " (5%) | Grand Total: ₹" . number_format($booking->final_amount ?? 0, 2),
            "text8" => number_format($booking->paid_amount ?? 0, 2),
            "text9" => number_format($booking->due_amount ?? 0, 2),
            "text10" => "https://www.enlivetrips.com/terms"
        ]);
    } catch (\Exception $e) {
        Log::error('WhatsApp API Failed: ' . $e->getMessage());
    }

    return response()->json([
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => $bookingId
    ]);
}
    public function send_email($email)
    {

        $booking = Bookings::first();
        $package = Packages::with('destination')->find($booking->package_id);

        Mail::to($email)->send(new BookingConfirmedMail($booking, $package->destination, $package->itinerary_pdf));
        return response()->json([
            'success' => true,
            'message' => 'Mail send to ' . $email
        ]);
    }
    public function popup_enquiry(Request $request)
    {
        $validated = $request->validate([
            'fname'   => 'required|string|max:255',
            'lname'   => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'email'   => 'nullable|email|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        // ✅ Store in DB
        $popupForm = PopupForms::create($validated);
        event(new \App\Events\PopupFormCreated($popupForm));
        // ✅ Return JSON response
        return response()->json([
            'status'  => true,
            'message' => 'Enquiry submitted successfully.'
        ], 201);
    }
    public function send_newsletter(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:newsletters,email',
        ]);

        $newsletter = Newsletters::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Newsletter submitted successfully.'
        ], 201);
    }
}
