<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['verifikasi_akun'] === null || $data['verifikasi_akun'] === '' || $data['verifikasi_akun'] === 'Belum Diverifikasi') {
            $data['verifikasi_akun'] = null;
        }
        return $data;
    }
}
