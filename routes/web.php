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

// =============================================
// ADMIN: Secure Resume Download (Filament auth protected)
// =============================================
Route::get('/admin/career-applications/{id}/download-resume', function ($id) {
    // Must be logged in via Filament
    if (!auth()->check()) {
        abort(403, 'Unauthorized');
    }

    $application = \App\Models\CareerApplication::findOrFail($id);

    if (!$application->resume || !\Illuminate\Support\Facades\Storage::disk('local')->exists($application->resume)) {
        abort(404, 'Resume file not found.');
    }

    $filename = 'resume_' . \Illuminate\Support\Str::slug($application->name) . '_' . $application->id . '.pdf';

    return \Illuminate\Support\Facades\Storage::disk('local')->download(
        $application->resume,
        $filename
    );
})->name('admin.career-applications.resume');