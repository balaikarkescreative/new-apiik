<?php

namespace App\Filament\Resources\LaporanPemanfaatanIcvResource\Pages;

use App\Filament\Resources\LaporanPemanfaatanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanPemanfaatanIcv extends EditRecord
{
    protected static string $resource = LaporanPemanfaatanIcvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
