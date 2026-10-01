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

// Download ZIP dokumen izin
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/izin/{record}/download-zip', [\App\Http\Controllers\IzinZipController::class, 'download'])
        ->name('izin.download-zip');

    // Batch Export routes with real-time progress
    Route::post('/admin/export/process-chunk', [\App\Http\Controllers\ExportBatchController::class, 'processChunk'])
        ->name('export.process-chunk');
    Route::post('/admin/export/finalize', [\App\Http\Controllers\ExportBatchController::class, 'finalize'])
        ->name('export.finalize');
    Route::post('/admin/export/cancel', [\App\Http\Controllers\ExportBatchController::class, 'cancel'])
        ->name('export.cancel');
    Route::get('/admin/export/download/{token}', [\App\Http\Controllers\ExportBatchController::class, 'download'])
        ->name('export.download');
});
