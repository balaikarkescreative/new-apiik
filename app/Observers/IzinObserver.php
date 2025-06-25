<?php

namespace App\Observers;

use App\Models\Izin;
use App\Models\IzinDokumen;
use App\Models\IzinVisitasi;
use App\Models\IzinPersetujuan;
use App\Models\IzinDokumenPks;

class IzinObserver
{
    /**
     * Handle the Izin "created" event.
     */
    public function created(Izin $izin): void
    {
        IzinDokumen::create([
            'izin_id' => $izin->id,
        ]);

        // IzinVisitasi::create([
        //     'id_izin' => $izin->id,
        // ]);
    }

    /**
     * Handle the Izin "updated" event.
     */
    public function updated(Izin $izin): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $fileField = 'file' . $i;
            if ($izin->isDirty($fileField) && $izin->$fileField) {
                // \Storage::delete($izin->getOriginal($fileField));
                // delete the old file if it exists
                if ($izin->getOriginal($fileField)) {
                    \Storage::delete($izin->getOriginal($fileField));
                }
            }
        }
    }

    /**
     * Handle the Izin "deleted" event.
     */
    public function deleted(Izin $izin): void
    {
        IzinDokumen::where('izin_id', $izin->id)->delete();

        for ($i = 1; $i <= 14; $i++) {
            $fileField = 'file' . $i;
            if ($izin->$fileField) {
                \Storage::delete($izin->$fileField);
            }
        }
    }

    /**
     * Handle the Izin "restored" event.
     */
    public function restored(Izin $izin): void
    {
        //
    }

    /**
     * Handle the Izin "force deleted" event.
     */
    public function forceDeleted(Izin $izin): void
    {
        //
    }
}
