<?php

namespace App\Filament\Resources\VideoPanduanResource\Pages;

use App\Filament\Resources\VideoPanduanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideoPanduan extends EditRecord
{
    protected static string $resource = VideoPanduanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
