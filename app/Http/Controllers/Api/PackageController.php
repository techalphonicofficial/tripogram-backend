<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActiveCosts;
use App\Models\ContactEnquiries;
use App\Models\Feedbacks;
use App\Models\PackageDates;
use App\Models\Packages;
use App\Models\RequestCallBacks;
use Illuminate\Http\Request;
use App\Models\PaymentLinks;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Settings;
// use Illuminate\Support\Facades\DB;
use DB;
use Illuminate\Support\Facades\Http;
use App\Models\Feedback;
use App\Models\Bookings;
use App\Models\PackageMapWithTrip;
use App\Models\Trips;
class PackageController extends Controller
{

    public function feedbacktemplate(Request $request)
    {
        $getbookingsdata = Bookings::with([
            'members.coupon.coupon',
            'package:id,title,slug,thumbnail,starting_price,duration,pickup,drop'
        ])
            ->whereDate('end_date', now())
            ->where('cron_job', 0)
            ->where('status', 'confirmed')
            ->select(
                'id',
                'booking_id',
                'package_id',
                'start_date',
                'end_date',
                'pickup',
                'drop',
                'cron_job'
            )
            ->orderBy('end_date', 'desc')
            ->get();

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($getbookingsdata as $booking) {
            // Duplicate contacts remove
            $uniqueMembers = collect($booking->members)->unique('contact');

            $bookingSuccess = false;

            foreach ($uniqueMembers as $member) {
                // Skip empty contact
                if (empty($member->contact)) {
                    $skippedCount++;
                    continue;
                }

                // ✅ Sirf tabhi call karo jab coupon ho
                if ($member->coupon && $member->coupon->coupon_amount) {
                    $result = $this->sendWhatsAppFeedback($booking, $member);

                    if ($result) {
                        $successCount++;

                        $bookingSuccess = true;
                    } else {
                        $failCount++;
                    }

                    // Delay for rate limit
                    usleep(500000);
                } else {
                    $skippedCount++;
                }
            }

            // Mark cron completed
            if ($bookingSuccess) {
                $booking->update([
                    'cron_job' => 1
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback messages processed successfully',
            'total_bookings' => count($getbookingsdata),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    private function sendWhatsAppFeedback($booking, $member)
    {
        try {
            $packageName = strip_tags($booking->package->title ?? 'Package');
            $memberName = trim($member->name ?? 'Valued Customer');
            $startDate = \Carbon\Carbon::parse($member->start_date ?? $booking->start_date)->format('d-m-Y');

            // Coupon code aur amount nikal
            $couponCode = $member->coupon->coupon->code ?? null;
            $couponAmount = $member->coupon->coupon_amount ?? null;

            $feedbackUrl = "https://www.enlivetrips.com/feedback?" . http_build_query([
                'booking_id' => $booking->id,
                'member_id' => $member->id,
                'name' => $memberName,
                'contact' => $member->contact,
                'package_name' => $packageName,
                'start_date' => $startDate,
                'coupon_code' => $couponCode,
                'coupon_amount' => $couponAmount
            ]);

            $data = [
                "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
                "phone" => $member->contact,
                "template_name" => "whatsapp_feedback_template",
                "template_language" => "EN_US",
                "text1" => $memberName,
                "text2" => $packageName,
                "text3" => $feedbackUrl,
                "text4" => $couponCode,
                "text5" => $couponAmount,
            ];

            \Log::info('WhatsApp Payload', $data);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.sendinai.com/sender",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                \Log::error("WhatsApp CURL Error: " . curl_error($ch));
                curl_close($ch);
                return false;
            }

            curl_close($ch);
            \Log::info("WhatsApp API Response", [
                'http_code' => $httpCode,
                'response' => $response
            ]);

            return $httpCode == 200;

        } catch (\Exception $e) {
            \Log::error("WhatsApp Feedback Exception", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
            return false;
        }
    }
    public function resendtemplate(Request $request)
    {
        $data = DB::table('payment_links as pl')
            ->join('bookings as b', 'pl.booking_id', '=', 'b.id')
            ->join('packages as p', 'b.package_id', '=', 'p.id')
            ->whereDate('pl.expire_at', Carbon::today())
            ->where('pl.status', 'pending')
            ->where('pl.cron_job', 0)
            ->select(
                'pl.id',
                'pl.booking_id',
                'pl.payment_link',
                'b.booking_id as bookings',
                'b.start_date as start_date',
                'b.full_name',
                'b.phone',
                'p.day',
                'p.title',
            )
            ->get();

        foreach ($data as $item) {

            // ✅ response store karo
            $response = Http::post("https://api.sendinai.com/sender", [
                "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
                "phone" => $item->phone, // ✅ fix
                "template_name" => "booking_confirmation_with_payment_link",
                "template_language" => "EN_US",

                "text1" => $item->full_name,
                "text2" => $item->bookings,
                "text3" => $item->title,
                "text4" => Carbon::parse($item->start_date)->toDateString(),
                "text5" => $item->payment_link,

            ]);

            // ✅ success check
            if ($response->successful()) {
                DB::table('payment_links')
                    ->where('id', $item->id)
                    ->update([
                        'cron_job' => 1,
                        'updated_at' => now()
                    ]);
            }
        }

        // ✅ return loop ke bahar hona chahiye
        return response()->json([
            "success" => true,
            "count" => count($data)
        ]);
    }


    public function feedbackPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'nullable',
            'member_id' => 'nullable',

            'name' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'destination' => 'nullable|string|max:255',

            'departure_date' => 'nullable|date',

            'travel_rating' => 'nullable|numeric|min:1|max:5',
            'stay_rating' => 'nullable|numeric|min:1|max:5',
            'meal_rating' => 'nullable|numeric|min:1|max:5',
            'captain_rating' => 'nullable|numeric|min:1|max:5',
            'itinerary_rating' => 'nullable|numeric|min:1|max:5',

            'overall_rating' => 'nullable|numeric|min:1|max:5',

            'suggestion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback = Feedback::create([
            'booking_id' => $request->booking_id,
            'member_id' => $request->member_id,

            'name' => $request->name,
            'contact' => $request->contact,
            'destination' => $request->destination,

            'departure_date' => $request->departure_date,

            'travel_rating' => $request->travel_rating,
            'stay_rating' => $request->stay_rating,
            'meal_rating' => $request->meal_rating,
            'captain_rating' => $request->captain_rating,
            'itinerary_rating' => $request->itinerary_rating,

            'overall_rating' => $request->overall_rating,

            'suggestion' => $request->suggestion,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feedback submitted successfully',
            'data' => $feedback
        ], 201);
    }

    public function gtm()
    {
        $get_data = Settings::select('id', 'gtm_header', 'gtm_footer')->first();

        return $get_data;
    }
    public function convertItinerary()
    {
        $packages = Packages::select('id', 'itinerary')->get();

        foreach ($packages as $package) {

            $html = $package->itinerary;

            // skip agar empty
            if (!$html)
                continue;

            preg_match_all('/<p><strong>(.*?)<\/strong><\/p>\s*<ul>(.*?)<\/ul>/s', $html, $matches);

            $result = [];

            foreach ($matches[1] as $index => $heading) {

                preg_match_all('/<li>(.*?)<\/li>/', $matches[2][$index], $points);

                $result[] = [
                    "heading" => trim(strip_tags($heading)),
                    "content" => array_map(function ($item) {
                        return trim(strip_tags($item));
                    }, $points[1])
                ];
            }

            Packages::where('id', $package->id)->update([
                'itinerary' => json_encode($result)
            ]);
        }
    }

    public function expirePackages()
    {

        $today = Carbon::now('Asia/Kolkata')->toDateString();


        // Step 1: Past dates ko inactive karo
        $data = DB::table('package_dates')
            ->whereDate('start_date', '<', $today)
            ->update([
                'status' => 'closed',
                'updated_at' => now()
            ]);


        // Step 2: Packages jinme koi bhi active/open date nahi hai
        $expiredPackageIds = DB::table('packages')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('package_dates')
                    ->whereColumn('package_dates.package_id', 'packages.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('package_dates')
                    ->whereColumn('package_dates.package_id', 'packages.id')
                    ->where('status', 'open'); // ya jo bhi active status hai
            })
            ->pluck('id');

        // Step 3: Un packages ko deactivate karo


        return "Packages & dates updated successfully.";
    }
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $page = $request->page ?? 1;

        $query = Packages::select(
            'id',
            'trip_id',
            'thumbnail',
            'title',
            'slug',
            'duration',
            'starting_price',
            'pickup',
            'drop',
            'is_trending'
        )->where('is_active', 1);

        // ✅ Trip filter
        if ($request->trip) {
            $trip = Trips::where('slug', $request->trip)->first();

            if ($trip) {
                $packageIds = PackageMapWithTrip::where('trip_id', $trip->id)
                    ->pluck('package_id')
                    ->toArray();

                $query->whereIn('id', $packageIds);

            } else {
                return response()->json([]);
            }
        }

        // ❌ whereHas hata diya (warna packages hide ho jaate)

        $packages = $query
            ->with([
                'activeCosts' => fn($q) => $q->where('show_on_website', 1),

                // ✅ Dates sirf load hongi agar available hain
                'packageDates' => fn($q) => $q->where('status', '!=', 'closed')
                    ->orderBy('start_date', 'asc')
            ])
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('is_trending')
            ->paginate($limit, ['*'], 'page', $page)
            ->items();

        return response()->json($packages);
    }

    public function trending(Request $request)
    {
        $packages = Packages::select(
            'id',
            'thumbnail',
            'title',
            'slug',
            'duration',
            'starting_price',
            'pickup',
            'drop',
            'is_trending'
        )
            ->where('is_active', true)
            ->where('is_trending', true)

            // Only packages having non-closed dates
            ->whereHas('packageDates', function ($query) {
                $query->where('status', '!=', 'closed');
            })

            ->with([
                'packageDates' => function ($query) {
                    $query->where('status', '!=', 'closed')
                        ->orderBy('start_date', 'asc');
                }
            ])

            ->orderByDesc('is_trending')
            ->get();

        return response()->json($packages);
    }

    public function single_package($slug)
    {
        $packages = Packages::with([
            'activeCosts' => function ($query) {
                $query->where('show_on_website', 1);
            },
            'packageDates' => function ($query) {
                $query->where('status', 'open')
                    ->orderBy('start_date', 'asc'); // sorting
            },
            'trips',
            'destination',
            'addonSchema'
        ])
            ->where('slug', $slug)
            ->first();

        return response()->json($packages);
    }
    public function request_call_back(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|integer|exists:packages,id',
            'full_name' => 'required|string|min:3|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'required|digits_between:10,15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 201);
        }

        $data = $validator->validated();

        // sanitize
        $data['full_name'] = strip_tags($data['full_name']);
        //  $data['email']     = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $data['phone'] = filter_var($data['phone'], FILTER_SANITIZE_NUMBER_INT);

        // save in DB
        RequestCallBacks::create($data);

        // get package
        $package = Packages::find($data['package_id']);

        // 🔥 PRIVYR WEBHOOK CALL
        $payload = [
            'name' => $data['full_name'],
            'lead_source' => 'www.tripogramclub.com.com',

            //'email'       => $data['email'],
            'phone' => $data['phone'],
            'other_fields' => [
                'Package' => $package->title ?? ''

            ]
        ];

        // try {
        //     $ch = curl_init();

        //     curl_setopt_array($ch, [
        //         CURLOPT_URL => 'https://www.privyr.com/integrations/api/v1/incoming-webhook',
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_POST => true,
        //         CURLOPT_POSTFIELDS => json_encode($payload),
        //         CURLOPT_HTTPHEADER => [
        //             'X-TOKEN: Sl0tLliY',
        //             'Content-Type: application/json'
        //         ],
        //         CURLOPT_TIMEOUT => 10,
        //     ]);

        //     $response = curl_exec($ch);
        //     curl_close($ch);

        // } catch (\Exception $e) {
        //     // optional: log error
        //     \Log::error('Privyr webhook failed: '.$e->getMessage());
        // }

        return response()->json([
            'success' => true,
            'message' => 'Request callback created successfully',
        ], 200);
    }
    public function costs_and_dates($slug)
    {
        $package = Packages::where('slug', $slug)->firstOrFail();


        $activeCosts = ActiveCosts::where('package_id', $package->id)
            ->where('show_on_website', 1)->get();

        $packageDates = PackageDates::where('package_id', $package->id)->get();

        return response()->json(["packageDates" => $packageDates, "activeCosts" => $activeCosts, "package" => $package]);
    }


    public function send_enquiry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => 'required|digits_between:10,15',
            'age' => 'nullable|integer|min:1|max:120',
            'destination' => 'required|string|max:255',
            'travel_date' => 'nullable|date|after:today',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // save enquiry
        $enquiry = ContactEnquiries::create([
            'name' => strip_tags($request->name),
            'email' => strtolower(trim($request->email)),
            'phone' => preg_replace('/\D/', '', $request->phone),
            'age' => $request->age,
            'destination' => strip_tags($request->destination),
            'travel_date' => $request->travel_date,
            'message' => strip_tags($request->message),
        ]);

        // 🔥 PRIVYR WEBHOOK CALL (same as callback API)
        $payload = [
            'name' => $request->name,
            'lead_source' => 'www.enlivetrips.com',
            'email' => $request->email,
            'phone' => preg_replace('/\D/', '', $request->phone),
            'other_fields' => [
                'Age' => $request->age,
                'Destination' => $request->destination,
                'Travel_date' => $request->travel_date
                    ? date("d M, Y", strtotime($request->travel_date))
                    : '',
                'Message' => strip_tags($request->message),
            ]
        ];

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://www.privyr.com/integrations/api/v1/incoming-webhook',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'X-TOKEN: Sl0tLliY',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 10,
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                \Log::error('Privyr CURL Error: ' . curl_error($ch));
            }

            curl_close($ch);

            \Log::info('Privyr Response: ' . $response);

        } catch (\Exception $e) {
            \Log::error('Privyr webhook failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Enquiry submitted successfully!'
        ], 201);
    }

    public function send_feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required|string|max:191',
            'l_name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|string|max:15',
            'feedback' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 201);
        }

        $feedback = Feedbacks::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully.',
        ], 200);
    }

    public function groupedDates()
    {
        $dates = PackageDates::with('package')
            ->whereHas('package', function ($q) {
                $q->where('is_active', 1);
            })
            ->where('status', '!=', 'closed')
            ->orderBy('start_date', 'asc')
            ->get();

        $grouped = $dates->groupBy(function ($date) {
            // Group by month-year
            return Carbon::parse($date->start_date)->format("M-y");
        })->map(function ($items, $month) {
            $days = [];

            $uniqueItems = $items->unique('start_date');

            foreach ($uniqueItems as $item) {
                $carbonDate = Carbon::parse($item->start_date);

                $days[] = [
                    'date' => (int) $carbonDate->format("j"),
                    'day' => $carbonDate->format("D"),
                    'full_date' => $item->start_date,
                ];
            }

            return [
                'label' => $month,
                'days' => $days,
            ];
        })->values();

        return response()->json($grouped);
    }
    public function search_packages($search)
    {
        $package = Packages::select('title', 'slug')->where('title', 'like', "%{$search}%")->limit(5)->get();
        return response()->json($package);
    }
}
