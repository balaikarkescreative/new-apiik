<?php

namespace App\Filament\Resources\KotakabResource\Pages;

use App\Filament\Resources\KotakabResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKotakab extends EditRecord
{
    protected static string $resource = KotakabResource::class;

    protected static ?string $title = 'Edit Kota/Kabupaten';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
