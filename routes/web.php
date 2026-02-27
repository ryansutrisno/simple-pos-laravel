<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Backup download route
Route::get('/admin/backups/download/{file}', function ($file) {
    $path = $file;

    if (! Storage::disk('backups')->exists($path)) {
        abort(404);
    }

    return Storage::disk('backups')->download($path);
})->name('backup.download')->middleware('auth');
