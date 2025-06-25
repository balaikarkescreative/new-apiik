<?php

namespace App\Filament\Resources\SuratPersetujuanIcvResource\Pages;

use App\Filament\Resources\SuratPersetujuanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuratPersetujuanIcv extends EditRecord
{
    protected static string $resource = SuratPersetujuanIcvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
