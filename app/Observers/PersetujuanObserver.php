<?php

namespace App\Observers;

use App\Models\IzinPersetujuan;
use Storage;

class PersetujuanObserver
{
    public function updating(IzinPersetujuan $izinPersetujuan): void
    {
        // Cek apakah file_persetujuan berubah
        if ($izinPersetujuan->isDirty('file_persetujuan')) {
            $originalFile = $izinPersetujuan->getOriginal('file_persetujuan');

            // Hapus file lama jika ada
            if ($originalFile && Storage::disk('public')->exists($originalFile)) {
                Storage::disk('public')->delete($originalFile);
            }
        }

        // if file_persetujuan is null, set status to 0 and delete if it exists
        if (is_null($izinPersetujuan->file_persetujuan)) {
            $izinPersetujuan->status = 0; // Set status to 0 if file_persetujuan is null
            $originalFile = $izinPersetujuan->getOriginal('file_persetujuan');
            if ($originalFile && Storage::disk('public')->exists($originalFile)) {
                Storage::disk('public')->delete($originalFile);
            }

        }
    }
}
