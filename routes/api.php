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


Route::middleware([CheckApp::class])->group(function () {

  
Route::get('/convert-itinerary', [PackageController::class,'convertItinerary']);
Route::post('/feedback-post',[PackageController::class,'feedbackPost']);
Route::get('/get-gtm',[PackageController::class,'gtm']);
Route::prefix('packages')->controller(PackageController::class)->name('packages.')->group(function () {
    Route::get('/', 'index')->name('index');
	Route::get('/expire', 'expirePackages');
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
    Route::post('/booking-information','bookinginformationupdate')->name('bookinginformationupdate');
    Route::get('/get-booking','get_booking')->name('get_booking');
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
});