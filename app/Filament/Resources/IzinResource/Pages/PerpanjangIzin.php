<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use Filament\Resources\Pages\CreateRecord;

class PerpanjangIzin extends CreateRecord
{
    protected static string $resource = IzinResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['date_updated'] = now();
        $data['kategori_izin'] = 'Perpanjang Izin';
        $data['user_id'] = auth()->id();
        $data['tanggal_pengajuan'] = now();

        return $data;
    }

    public function getTitle(): string
    {
        return 'Perpanjang Izin';
    }

    public function getBreadcrumb(): string
    {
        return 'Perpanjang Izin';
    }
}
