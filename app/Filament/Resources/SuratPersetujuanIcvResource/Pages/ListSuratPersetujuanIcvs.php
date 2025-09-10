<?php

namespace App\Filament\Resources\SuratPersetujuanIcvResource\Pages;

use App\Filament\Resources\SuratPersetujuanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuratPersetujuanIcvs extends ListRecords
{
    protected static string $resource = SuratPersetujuanIcvResource::class;

    protected static ?string $title = 'Surat Persetujuan ICV';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
