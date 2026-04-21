<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MainPages;
use App\Models\PageSections;
use App\Models\Settings;
use App\Models\Trips;
use App\Models\Destinations;
use App\Models\Packages;
use App\Models\Blogs;

class MainPageController extends Controller
{
    public function index()
    {
        $blogs = MainPages::with('sections')->get();
        return response()->json($blogs);
    }
    public function single_page($id)
    {
        // return "S";
        // return $id;
        $blogs = MainPages::with('sections', 'addonSchemas')->where('id', $id)->first();
        
        // return $blogs;
        return response()->json($blogs);
    }
    public function single_section($page_id, $single_key)
    {
        $section = PageSections::where('page_id', $page_id)->where('section_key', $single_key)->first();
        return response()->json($section);
    }
    public function robots_txt()
    {
        $settings = Settings::select('robots_txt')->first();
        return response()->json($settings);
    }
    public function razorpay()
    {
        $settings = Settings::select('razorpay_key_id','package_amount_percent')->first();
        return response()->json($settings);
    }
    public function sitemap_xml()
    {
        $trips = Trips::select('slug', 'updated_at')->get()
            ->map(fn($item) => [
                'url' => 'trips/' . $item->slug,
                'lastmod' => $item->updated_at,
                'changefreq' => "weekly",
                'priority' => "0.8",
            ]);

        $destinations = Destinations::select('slug', 'updated_at')->get()
            ->map(fn($item) => [
                'url' => 'destination/' . $item->slug,
                'lastmod' => $item->updated_at,
                'changefreq' => "weekly",
                'priority' => "0.8",
            ]);

        $packages = Packages::select('slug', 'updated_at')->get()
            ->map(fn($item) => [
                'url' => $item->slug,
                'lastmod' => $item->updated_at,
                'changefreq' => "weekly",
                'priority' => "0.8",
            ]);

        $blogs = Blogs::select('slug', 'updated_at')->get()
            ->map(fn($item) => [
                'url' => 'blog/' . $item->slug,
                'lastmod' => $item->updated_at,
                'changefreq' => "monthly",
                'priority' => "0.8",
            ]);

        return response()->json(
            $packages->concat($destinations)->concat($trips)->concat($blogs)->values()
        );
    }
}
