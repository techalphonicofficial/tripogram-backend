<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Redirections;
use Illuminate\Http\Request;

class RedirectionsController extends Controller
{
    public function package_redirect($slug){
        $redirection = Redirections::where('from_url', $slug)->where('status',1)->first();
        return response()->json($redirection);
    }
}
