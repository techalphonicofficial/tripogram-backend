<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Destinations;

class DestinationController extends Controller
{
    public function index(){
        $destinations = Destinations::select(['name'])->get();
        return response()->json($destinations);
    }
    
     public function home(){
        $destinations = Destinations::select(['id', 'name', 'slug', 'thumbnail'])
        ->where('show_in_home', 1)
        ->withCount(['packages as active_packages_count' => function ($query) {
            $query->where('is_active', 1);
        }])
        ->get();
        
        return response()->json($destinations);
    }
  
  
    public function single_destinations($slug)
{
    $trip = Destinations::where('slug', $slug)->firstOrFail();

    $packages = $trip->packages()
        ->select('id', 'thumbnail', 'title', 'slug', 'duration', 'starting_price', 'pickup', 'drop', 'is_trending')
        ->with(['packageDates' => function ($query) {
            $query->where('status', '!=', 'closed')
                  ->orderBy('start_date', 'asc');
        }])
      	->orderBy('sort_order', 'asc')
        ->where('is_active', true)
        ->get();

    return response()->json([
        'trip' => $trip,
        'packages' => $packages
    ]);
}
}