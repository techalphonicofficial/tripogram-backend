<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if ($request->is('api/*')) {
        //     $origin = $request->headers->get('referer');
        //     $allowedDomains = [env('APP_WEB_URL'), env('APP_URL')];

        //     $originHost = $origin ? parse_url($origin, PHP_URL_HOST) : null;

        //     if (!$originHost || !in_array($originHost, array_map(fn($url) => parse_url($url, PHP_URL_HOST), $allowedDomains))) {
        //         return response()->json(['message' => 'Unauthorized'], 403);
        //     }
        // }

        return $next($request);
    }
}
