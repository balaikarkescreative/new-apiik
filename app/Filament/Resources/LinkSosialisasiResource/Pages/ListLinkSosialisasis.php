<?php

namespace App\Filament\Resources\LinkSosialisasiResource\Pages;

use App\Filament\Resources\LinkSosialisasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLinkSosialisasis extends ListRecords
{
    protected static string $resource = LinkSosialisasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Link Sosialisasi')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
