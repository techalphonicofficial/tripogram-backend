<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\MainPageController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Api\RedirectionsController;
use App\Http\Middleware\AuthApi;
use App\Http\Middleware\CheckApp;

use App\Http\Controllers\PostOrderController;
Route::post('/feedback-post', [PackageController::class, 'feedbackPost']);



Route::get('/test-sorry-feedback', function () {

    try {

        $phone = "917017026233"; // test number

        $data = [
            "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
            "phone" => $phone,
            "template_name" => "feedback_issue",
            "template_language" => "EN_US",
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.sendinai.com/sender",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            return [
                'status' => false,
                'error' => curl_error($ch)
            ];
        }

        curl_close($ch);

        return [
            'status' => true,
            'http_code' => $httpCode,
            'response' => json_decode($response, true)
        ];

    } catch (\Exception $e) {

        return [
            'status' => false,
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
        ];
    }
});
Route::get('/mail-config-check', function () {
    return [
        'mailer' => config('mail.default'),
        'host' => config('mail.mailers.smtp.host'),
        'username' => config('mail.mailers.smtp.username'),
        'from' => config('mail.from'),
    ];
});

Route::get('/sorry-feedback', [BookingController::class, 'sorryfeedback']);
Route::get('/mailssss', function () {

    $file = '/home/enlivetrips.com/public_html/dashboard.enlivetrips.com/vendor/filament/forms/src/helpers.php';

    if (file_exists($file)) {

        $content = file_get_contents($file);

        // remove namespace line
        $content = str_replace('namespace Filament\\Forms;', '', $content);

        file_put_contents($file, $content);

        return 'Namespace removed successfully';

    }

    return 'File not found';
});
Route::get('/mail-check', function () {

    try {
        \Log::info('MAIL CONFIG', config('mail'));

        Mail::raw('Test mail check', function ($message) {
            $message->to('ys0979727@gmail.com')
                ->subject('Mail Check');
        });

        return 'Mail sent trigger done';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
});
Route::get('/clear-cache-queue', function () {

    Artisan::call('optimize:clear');
    Artisan::call('queue:restart');

    return response()->json([
        'success' => true,
        'message' => 'Cache cleared and queue restarted successfully'
    ]);
});
Route::get('/mail-header-test', function () {

    Mail::raw('Header Test', function ($message) {

        $message->from(
            'booking@enlivetrips.com',
            'tripogramclub'
        );

        $message->to('ys0979727@gmail.com')
            ->subject('Header Test');
    });

    return 'sent';
});

Route::post('/again-booking', [BookingController::class, 'add_booking1']);
Route::get('/feedback-again', [PackageController::class, 'feedbacktemplate']);
Route::get('/whatsapp-hook', [PostOrderController::class, 'whatsapphook']);
Route::get('/email-hook', [PostOrderController::class, 'emailwhatsapp']);
Route::prefix('post-order')->group(function () {
    Route::post('/create-booking', [PostOrderController::class, 'createBooking']);
    Route::post('/update-payment', [PostOrderController::class, 'updatePayment']);
    Route::get('/get-booking', [PostOrderController::class, 'getBooking']);
});
Route::post('/razorpay_webhook', [BookingController::class, 'webhook']);
Route::get('packages/expire', [PackageController::class, 'expirePackages']);
Route::get('/resend-template', [PackageController::class, 'resendtemplate']);
//Route::middleware([CheckApp::class])->group(function () {


Route::post('/post-coupen', [BookingController::class, 'applyCoupon']);

Route::get('/convert-itinerary', [PackageController::class, 'convertItinerary']);

Route::get('/get-gtm', [PackageController::class, 'gtm']);
Route::prefix('packages')->controller(PackageController::class)->name('packages.')->group(function () {
    Route::get('/', 'index')->name('index');

    Route::get('/single/{slug}', 'single_package')->name('single_package');
    Route::post('/request-call-back', 'request_call_back')->name('request_call_back');
    Route::get('/{slug}/costs-and-dates', 'costs_and_dates')->name('costs_and_dates');
    Route::get('/grouped-dates', 'groupedDates')->name('groupedDates');
    Route::get('/trending', 'trending')->name('trending');
    Route::post('/send-enquiry', 'send_enquiry')->name('send_enquiry');
    Route::post('/send-feedback', 'send_feedback')->name('send_feedback');
    Route::get('/search/{search}', 'search_packages')->name('search_packages');
});


Route::prefix('trips')->controller(TripController::class)->name('trips.')->group(function () {
    Route::get('/', 'index')->name('index');

    Route::get('/single/{slug}', 'single_trips')->name('single_trips');
    Route::get('/home', 'home_trips')->name('home_trips');
    Route::get('/trips-with-packagecount', 'trips_with_packagecount')->name('trips_with_packagecount');
    Route::get('/trips-with-destination', 'trips_with_destination')->name('trips_with_destination');
});

Route::middleware(AuthApi::class)->prefix('blogs')->controller(BlogController::class)->name('blogs.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{slug}', 'single_blogs')->name('single_blogs');
    Route::get('/search/{text}', 'blogs_by_trip_destination')->name('blogs_by_trip_destination');
});

Route::prefix('pages')->controller(MainPageController::class)->name('pages.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{page_id}', 'single_page')->name('single_page');
    Route::get('/{page_id}/{single_key}', 'single_section')->name('single_section');
});

Route::prefix('booking')->controller(BookingController::class)->name('booking.')->group(function () {
    Route::post('/booking-information', 'bookinginformationupdate')->name('bookinginformationupdate');
    Route::get('/get-booking', 'get_booking')->name('get_booking');
    Route::post('/add-booking', 'add_booking')->name('add_booking');
    Route::post('/popup-enquiry', 'popup_enquiry')->name('popup_enquiry');
    Route::post('/send-newsletter', 'send_newsletter')->name('send_newsletter');
    Route::post('/send-email/{email}', 'send_email')->name('send_email');
});

Route::prefix('destinations')->controller(DestinationController::class)->name('destinations.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/single/{slug}', 'single_destinations')->name('single_destinations');
    Route::get('/home', 'home')->name('home');
});
Route::prefix('redirection')->controller(RedirectionsController::class)->name('redirection.')->group(function () {
    Route::get('/{slug}', 'package_redirect')->name('package_redirect');
});

Route::get('/sitemap-xml', [MainPageController::class, 'sitemap_xml'])->name('sitemap_xml');
Route::get('/robots-text', [MainPageController::class, 'robots_txt'])->name('robots_text');
Route::get('/razorpay', [MainPageController::class, 'razorpay'])->name('razorpay');
//});