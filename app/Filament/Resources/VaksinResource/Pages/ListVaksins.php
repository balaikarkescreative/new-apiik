<?php

namespace App\Filament\Resources\VaksinResource\Pages;

use App\Filament\Resources\VaksinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVaksins extends ListRecords
{
    protected static string $resource = VaksinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
