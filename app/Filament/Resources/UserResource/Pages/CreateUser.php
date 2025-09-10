<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Tambah RS/Klinik Baru';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $location = $data['location'] ?? null;

        if ($location) {
            $data['latitude'] = $location['lat'] ?? null;
            $data['longitude'] = $location['lng'] ?? null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // assign role 'user' to the newly created user
        $this->record->assignRole('user');
    }
}
