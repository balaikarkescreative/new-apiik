<?php

namespace App\Filament\Resources\KotakabResource\Pages;

use App\Filament\Resources\KotakabResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKotakabs extends ListRecords
{
    protected static string $resource = KotakabResource::class;

    protected static ?string $title = 'Kota/Kabupaten';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kota/Kabupaten')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
