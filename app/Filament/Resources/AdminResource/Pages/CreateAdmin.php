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
        $data['status_akun'] = 'Aktif';
        $data['is_blocked'] = false;
        $data['date_updated'] = now();

        // If simply_user_id is provided, sync initial password hash from SIMPLY
        if (! empty($data['simply_user_id'])) {
            $simplyUser = \App\Models\SimplyUser::find($data['simply_user_id']);
            if ($simplyUser) {
                $data['password'] = $simplyUser->password;
                if (empty($data['email'])) {
                    $data['email'] = null;
                }
            }
        }

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
