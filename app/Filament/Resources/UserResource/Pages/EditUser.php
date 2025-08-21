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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $data['location'] = [
                'lat' => $data['latitude'],
                'lng' => $data['longitude'],
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['verifikasi_akun'] === null || $data['verifikasi_akun'] === '' || $data['verifikasi_akun'] === 'Belum Diverifikasi') {
            $data['verifikasi_akun'] = null;
        }

        if (isset($data['location'])) {
            $data['latitude'] = $data['location']['lat'] ?? null;
            $data['longitude'] = $data['location']['lng'] ?? null;
        }

        return $data;
    }
}
