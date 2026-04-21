<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blogs;
use Illuminate\Http\Request;

class BlogController extends Controller
{
  public function index(Request $request)
{
    $blogs = Blogs::where('status',1)
        ->orderBy('id','DESC')
        ->paginate(10);

    return response()->json($blogs);
}
    
    public function single_blogs(Request $request, $slug)
    {
        // dd('sd');
        $blog = Blogs::with('details')->where('slug', $slug)->firstOrFail();
        
        $recentBlog = Blogs::where('id', '!=', $blog->id)->latest()->take(5)->get();
    
        return response()->json([ 'blog' => $blog, 'recentBlog' => $recentBlog ]);
    }
    public function blogs_by_trip_destination($text){
        $find = implode('|', array_filter(
            preg_split('/\s+/', $text),
            fn($w) => strlen(preg_replace('/[^a-zA-Z]/', '', $w)) > 4
        ));
        $blogs = Blogs::where('heading', 'REGEXP', $find)->orderBy('id',"DESC")->get();
        return response()->json($blogs);
    }
}
