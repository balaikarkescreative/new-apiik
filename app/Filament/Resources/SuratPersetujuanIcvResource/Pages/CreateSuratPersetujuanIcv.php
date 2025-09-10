<?php

namespace App\Filament\Resources\SuratPersetujuanIcvResource\Pages;

use App\Filament\Resources\SuratPersetujuanIcvResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSuratPersetujuanIcv extends CreateRecord
{
    protected static string $resource = SuratPersetujuanIcvResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // Jika user bukan admin dan id_akun kosong, set ke user yang login
        if ((! $user->hasRole('admin') && ! $user->hasRole('super_admin')) || empty($data['id_akun'])) {
            $data['id_akun'] = $user->id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
