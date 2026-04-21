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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Settings;
// use Illuminate\Support\Facades\DB;
use DB;
use App\Models\Feedback;
use App\Models\Bookings;
use App\Models\PackageMapWithTrip;
use App\Models\Trips;
class PackageController extends Controller
{


public function feedbackPost(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'nullable|string|max:255',
        'email' => 'required|email',
        'phone' => 'required|string|max:20',
        'message' => 'nullable|string',
        'rating' => 'required', // text hai DB me
        'booking_id' => 'nullable',
    ]);

 
    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $feedback = Feedback::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'message' => $request->message,
        'rating' => $request->rating,
        'booking_id' =>  $request->booking_id,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Feedback submitted successfully',
        'data' => $feedback
    ], 201);
}
  
public function gtm()
{
    $get_data = Settings::select('id','gtm_header','gtm_footer')->first();

    return $get_data;
}
public function convertItinerary()
{
   $packages = Packages::select('id','itinerary')->get();

foreach ($packages as $package) {

    $html = $package->itinerary;

    // skip agar empty
    if(!$html) continue;

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

    Packages::where('id',$package->id)->update([
        'itinerary' => json_encode($result)
    ]);
}
}
  
public function expirePackages() {
    $today = Carbon::today();

    // Step 1: Past dates ko inactive karo
    DB::table('package_dates')
        ->where('start_date', '<=', $today)
        ->update(['status' => 'closed']);

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
        'id','trip_id','thumbnail','title','slug',
        'duration','starting_price','pickup','drop','is_trending'
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
            'packageDates' => fn($q) => $q->where('start_date', '>', Carbon::today())
                ->where('status', '!=', 'closed')
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
        
        // ✅ Only include packages that have valid upcoming dates
        ->whereHas('packageDates', function ($query) {
            $query->where('start_date', '>', Carbon::today())
                  ->where('status', '!=', 'closed');
        })

        ->with([
            'packageDates' => function ($query) {
                $query->where('start_date', '>', Carbon::today())
                      ->where('status', '!=', 'closed')
                      ->orderBy('start_date', 'asc');
            }
        ])

        ->orderByDesc('is_trending') // optional but clean
        ->get();

    return response()->json($packages);
}
  
   public function single_package($slug)
{
    $packages = Packages::with([
        'activeCosts' => function ($query) {
            $query->where('show_on_website', 1); // 👈 yahi add karna hai
        },
        'packageDates' => function ($query) {
            $query->where('status', '!=', 'closed');
        },
        'trip',
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
        'full_name'  => 'required|string|min:3|max:100',
        'email'      => 'nullable|email|max:100',
        'phone'      => 'required|digits_between:10,15',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 201);
    }

    $data = $validator->validated();

    // sanitize
    $data['full_name'] = strip_tags($data['full_name']);
  //  $data['email']     = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $data['phone']     = filter_var($data['phone'], FILTER_SANITIZE_NUMBER_INT);

    // save in DB
    RequestCallBacks::create($data);

    // get package
    $package = Packages::find($data['package_id']);

    // 🔥 PRIVYR WEBHOOK CALL
    $payload = [
        'name'        => $data['full_name'],
        'lead_source' => 'www.enlivetrips.com',
      
        //'email'       => $data['email'],
        'phone'       => $data['phone'],
        'other_fields'=> [
            'Package' => $package->title ?? ''
          	
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
        curl_close($ch);

    } catch (\Exception $e) {
        // optional: log error
        \Log::error('Privyr webhook failed: '.$e->getMessage());
    }

    return response()->json([
        'success' => true,
        'message' => 'Request callback created successfully',
    ], 200);
}
    public function costs_and_dates($slug)
    {
        $package = Packages::where('slug', $slug)->firstOrFail();
        $activeCosts = ActiveCosts::where('package_id', $package->id)
          							->where('show_on_website',1)->get();
        $packageDates = PackageDates::where('package_id', $package->id)->get();

        return response()->json(["packageDates" => $packageDates, "activeCosts" => $activeCosts]);
    }
    public function send_enquiry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email'       => 'required|email:rfc,dns|max:255',
            'phone'       => 'required|digits_between:10,15',
            'age'         => 'nullable|integer|min:1|max:120',
            'destination' => 'required|string|max:255',
            'travel_date' => 'nullable|date|after:today',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $enquiry = ContactEnquiries::create([
            'name'        => strip_tags($request->name),
            'email'       => strtolower(trim($request->email)),
            'phone'       => preg_replace('/\D/', '', $request->phone),
            'age'         => $request->age,
            'destination' => strip_tags($request->destination),
            'travel_date' => $request->travel_date,
            'message' => strip_tags($request->message),
        ]);
        send_privyr($request->name, $request->email, $request->phone, "Contact Enquiries", [
            'Age'         => $request->age,
            'Destination' => $request->destination,
            'Travel_date' => date("d M, Y", strtotime($request->travel_date)),
            'Message' => strip_tags($request->message)
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Enquiry submitted successfully!'
        ], 201);
    }
    public function send_feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name'   => 'required|string|max:191',
            'l_name'   => 'required|string|max:191',
            'email'    => 'required|email|max:191',
            'phone'    => 'nullable|string|max:15',
            'feedback' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
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
        $dates = PackageDates::with('package')->whereHas('package', function ($q) {
            $q->where('is_active', 1);
        })->where('start_date', '>', Carbon::today())->orderBy('start_date', 'asc')->get();

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
                    'day'  => $carbonDate->format("D"),
                    'full_date'  => $item->start_date,
                ];
            }

            return [
                'label' => $month,
                'days'  => $days,
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
