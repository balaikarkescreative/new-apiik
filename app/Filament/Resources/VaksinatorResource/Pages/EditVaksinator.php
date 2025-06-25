<?php

namespace App\Filament\Resources\VaksinatorResource\Pages;

use App\Filament\Resources\VaksinatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVaksinator extends EditRecord
{
    protected static string $resource = VaksinatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
