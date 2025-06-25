<?php

namespace App\Filament\Resources\LaporanPenggunaanIcvResource\Pages;

use App\Filament\Resources\LaporanPenggunaanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanPenggunaanIcvs extends ListRecords
{
    protected static string $resource = LaporanPenggunaanIcvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
