<?php

use App\Http\Controllers\SanitizedFileUploadController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

// Override Livewire file upload endpoint dengan controller yang men-sanitasi
// nama file berkarakter khusus (emoji, tanda baca, dll) sebelum disimpan ke
// temp storage. Ini mencegah error "Unable to retrieve the file_size for file
// at location: livewire-tmp/livewire-tmp".
Route::post('/livewire/upload-file', [SanitizedFileUploadController::class, 'handle'])
    ->middleware(['web'])
    ->name('livewire.upload-file');

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
