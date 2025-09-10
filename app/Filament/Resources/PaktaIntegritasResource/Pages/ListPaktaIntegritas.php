<?php

namespace App\Filament\Resources\PaktaIntegritasResource\Pages;

use App\Filament\Resources\PaktaIntegritasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaktaIntegritas extends ListRecords
{
    protected static string $resource = PaktaIntegritasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pakta Integritas')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
