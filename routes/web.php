<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
   return redirect()->route('filament.admin.pages.dashboard');
});
Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage linked successfully!';
});

Route::get('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return 'optimize clear successfully!';
});