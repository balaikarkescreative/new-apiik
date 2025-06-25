<?php

namespace App\Filament\Resources\OperationalSettingResource\Pages;

use App\Filament\Resources\OperationalSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOperationalSetting extends EditRecord
{
    protected static string $resource = OperationalSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
