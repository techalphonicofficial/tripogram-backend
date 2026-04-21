<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trips;
use App\Models\Destinations;
use Illuminate\Http\Request;
use Carbon\Carbon;
class TripController extends Controller
{
    public function index(Request $request)
    {
        $trips = Trips::all();
        return response()->json($trips);
    }
  
public function single_trips(Request $request, $slug)
{
    $trip = Trips::where('slug', $slug)->firstOrFail();


  
    $packages = $trip->packagess()
        ->select(
            'packages.id',           // ✅ packages. prefix
            'packages.trip_id',      // ✅ packages. prefix
            'packages.thumbnail',
            'packages.title',
            'packages.slug',
            'packages.duration',
            'packages.starting_price',
            'packages.pickup',
            'packages.drop',
            'packages.is_trending'
        )
          ->where('packages.is_active', true)

        // ✅ Filter packages having valid upcoming dates
        ->whereHas('packageDates', function ($query) {
            $query->whereDate('start_date', '>', Carbon::today())
                  ->where('status', '!=', 'closed');
        })

        ->with([
            'activeCosts', // 👈 add if needed (like in previous API)

            'packageDates' => function ($query) {
                $query->whereDate('start_date', '>', Carbon::today())
                      ->where('status', '!=', 'closed')
                      ->orderBy('start_date', 'asc');
            }
        ])

        // ✅ Better ordering priority
       // ->orderByDesc('is_trending') // show trending first
        ->orderBy('sort_order', 'asc')

        ->get();

    return response()->json([
        'trip' => $trip,
        'packages' => $packages
    ]);
}
    public function home_trips()
    {
        $home = Trips::select(['id','heading','slug','thumbnail'])->where('show_in_home', 1)->get();
        return response()->json($home);
    }
  
public function trips_with_packagecount()
{
  	
    $trips = Trips::withCount([
        'tripswithpac as active_packages_count' => function ($query) {
          
                  $query->whereHas('packagse', function ($q) {
                      $q->where('is_active', 1);
                  });
        }
    ])
    ->having('active_packages_count', '>', 0)
    ->get()
    ->makeHidden(['content', 'banner', 'thumbnail', 'meta_title', 'meta_description', 'meta_keywords']);
    
    return response()->json($trips);
}
    
    public function trips_with_destination()
    {
        $trips = Trips::all();
        $destinations = Destinations::all();
    
        return response()->json(['trips' => $trips, 'destinations' => $destinations]);
    }
}
