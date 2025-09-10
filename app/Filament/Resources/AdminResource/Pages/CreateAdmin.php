<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Resources/AdminResource/Pages/CreateAdmin.php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan field yang dibutuhkan ada
        $data['verifikasi_akun'] = 'Diterima';
        $data['date_updated'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Admin berhasil ditambahkan';
    }
}
