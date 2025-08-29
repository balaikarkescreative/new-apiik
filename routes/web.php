<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/maintenance', function () {
    return view('maintenance');
})->name('maintenance');

// Video streaming routes (tanpa autentikasi untuk akses video)
Route::middleware(['web'])->group(function () {
    Route::get('/video/stream/{id}', [VideoController::class, 'stream'])->name('video.stream');
    Route::get('/video/metadata/{id}', [VideoController::class, 'metadata'])->name('video.metadata');
    Route::get('/video/test/{id}', [VideoController::class, 'test'])->name('video.test');
});