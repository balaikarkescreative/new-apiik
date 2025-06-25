<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Models\IzinDokumen;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIzin extends EditRecord
{
    protected static string $resource = IzinResource::class;

    protected function afterSave (): void
    {
        $cek_izin = IzinDokumen::where('izin_id', $this->record->id)->first();

        if ($cek_izin) {
            $cek_izin = $cek_izin->delete();
        }
    }

    protected function getRedirectUrl(): string
    {
        if (auth()->user()->hasRole('admin')) {
            return $this->getResource()::getUrl();
        } else {
            return $this->getResource()::getUrl('daftar-pengajuan');
        }
    }

}
