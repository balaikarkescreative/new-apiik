<?php

namespace App\Filament\Resources\VideoPanduanResource\Pages;

use App\Filament\Resources\VideoPanduanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoPanduans extends ListRecords
{
    protected static string $resource = VideoPanduanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
