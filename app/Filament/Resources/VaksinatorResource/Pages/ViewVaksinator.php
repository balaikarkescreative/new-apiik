<?php

namespace App\Filament\Resources\VaksinatorResource\Pages;

use App\Filament\Resources\VaksinatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVaksinator extends ViewRecord
{
    protected static string $resource = VaksinatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
