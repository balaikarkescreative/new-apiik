<?php

namespace App\Filament\Resources\LaporanPemanfaatanIcvResource\Pages;

use App\Filament\Resources\LaporanPemanfaatanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanPemanfaatanIcv extends CreateRecord
{
    protected static string $resource = LaporanPemanfaatanIcvResource::class;

    protected static bool $canCreateAnother = false;

    public function getRedirectUrl() : string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika user bukan admin dan id_klinik kosong, set ke user yang login
        if (!$user->hasRole('admin') || empty($data['id_klinik'])) {
            $data['id_klinik'] = $user->id;
        }

        return $data;
    }


}
