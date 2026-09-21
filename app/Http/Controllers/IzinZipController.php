<?php

namespace App\Http\Controllers;

use App\Models\Izin;
use App\Services\IzinZipService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IzinZipController extends Controller
{
    public function download(Izin $record, IzinZipService $zipService): Response
    {
        $user = auth()->user();

        // Verifikasi hak akses: admin atau pemilik permohonan
        if (!$user) {
            abort(401);
        }

        if (!$user->hasRole(['admin']) && $user->id !== $record->user_id) {
            abort(403, 'Akses ditolak.');
        }

        // Pastikan relasi dokumen dimuat
        $record->loadMissing(['user', 'izinVisitasi', 'izinDokumenPks', 'izinPersetujuan']);

        $response = $zipService->downloadZip($record);

        if (!$response) {
            Notification::make()
                ->title('Dokumen Tidak Tersedia')
                ->warning()
                ->body('Permohonan izin ini belum memiliki berkas dokumen yang dapat diunduh.')
                ->send();

            return back();
        }

        return $response;
    }
}
