<?php

namespace App\Filament\Resources\VideoPanduanResource\Pages;

use App\Filament\Resources\VideoPanduanResource;
use App\Models\VideoPanduan;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoPanduan extends CreateRecord
{
    protected static string $resource = VideoPanduanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto set order jika tidak diisi
        if (! isset($data['order']) || $data['order'] === 0) {
            $maxOrder = VideoPanduan::max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
        }

        return $data;
    }
}
