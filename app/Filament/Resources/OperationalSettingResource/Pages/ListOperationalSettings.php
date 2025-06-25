<?php

namespace App\Filament\Resources\OperationalSettingResource\Pages;

use App\Filament\Resources\OperationalSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOperationalSettings extends ListRecords
{
    protected static string $resource = OperationalSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
