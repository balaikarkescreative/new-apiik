<?php

namespace App\Filament\Resources\LaporanGeotaggingResource\Pages;

use App\Filament\Resources\LaporanGeotaggingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanGeotaggings extends ListRecords
{
    protected static string $resource = LaporanGeotaggingResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Laporan Geotagging')
                ->icon('heroicon-o-plus')
                ->color('primary')
        ];
    }
}
