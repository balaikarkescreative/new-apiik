<?php

namespace App\Filament\Resources\VaksinResource\Pages;

use App\Filament\Resources\VaksinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVaksin extends EditRecord
{
    protected static string $resource = VaksinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
