<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        RateLimiter::for('enquiry', function (Request $request) {
            return Limit::perMinutes(2)->by($request->ip());
        });

        // ✅ Dynamic URL & HTTPS support for ngrok local development
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && str_contains($_SERVER['HTTP_X_FORWARDED_HOST'], 'ngrok')) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
            $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http';
            config(['app.url' => "$proto://$host"]);
            \Illuminate\Support\Facades\URL::forceRootUrl("$proto://$host");
            if ($proto === 'https') {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }
    }
  
}

