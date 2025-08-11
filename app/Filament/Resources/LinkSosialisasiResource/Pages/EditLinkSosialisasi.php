<?php

namespace App\Filament\Resources\LinkSosialisasiResource\Pages;

use App\Filament\Resources\LinkSosialisasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLinkSosialisasi extends EditRecord
{
    protected static string $resource = LinkSosialisasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
