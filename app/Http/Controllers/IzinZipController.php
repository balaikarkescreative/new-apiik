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

        // Verifikasi hak akses: admin, pemilik permohonan, atau dokter PIC yang ditugaskan
        if (! $user) {
            abort(401);
        }

        $isAdmin = $user->hasRole('admin');
        $isOwner = $user->id === $record->user_id;
        $isAssignedDoctor = $user->hasRole('dokter') && (int) $record->pic_dokter_id === (int) $user->id;

        if (! $isAdmin && ! $isOwner && ! $isAssignedDoctor) {
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
