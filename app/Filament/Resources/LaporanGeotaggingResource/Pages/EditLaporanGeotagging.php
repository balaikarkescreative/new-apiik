<?php

namespace App\Filament\Resources\LaporanGeotaggingResource\Pages;

use App\Filament\Resources\LaporanGeotaggingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanGeotagging extends EditRecord
{
    protected static string $resource = LaporanGeotaggingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
