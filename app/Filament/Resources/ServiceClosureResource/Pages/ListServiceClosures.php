<?php

namespace App\Filament\Resources\ServiceClosureResource\Pages;

use App\Filament\Resources\ServiceClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceClosures extends ListRecords
{
    protected static string $resource = ServiceClosureResource::class;
    protected static ?string $title = 'Kelola Layanan';


    protected function getHeaderActions(): array
    {
        return [
            // Hapus CreateAction karena tidak boleh menambah data
        ];
    }
}
