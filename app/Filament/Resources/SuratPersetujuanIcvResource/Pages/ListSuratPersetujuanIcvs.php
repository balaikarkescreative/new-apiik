<?php

namespace App\Filament\Resources\SuratPersetujuanIcvResource\Pages;

use App\Filament\Resources\SuratPersetujuanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuratPersetujuanIcvs extends ListRecords
{
    protected static string $resource = SuratPersetujuanIcvResource::class;

    protected static ?string $title = 'Surat Persetujuan Pelaksanaan Vaksinasi Internasional';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}
