<?php

namespace App\Filament\Resources\LaporanPemanfaatanIcvResource\Pages;

use App\Filament\Resources\LaporanPemanfaatanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanPemanfaatanIcvs extends ListRecords
{
    protected static string $resource = LaporanPemanfaatanIcvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
