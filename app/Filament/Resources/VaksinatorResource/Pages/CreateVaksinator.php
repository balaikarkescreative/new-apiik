<?php

namespace App\Filament\Resources\VaksinatorResource\Pages;

use App\Filament\Resources\VaksinatorResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVaksinator extends CreateRecord
{
    protected static string $resource = VaksinatorResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()->hasRole('user')) {
            $data['id_akun'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Data Vaksinator Berhasil Ditambahkan')
            ->body('Data vaksinator '.$this->record->nama_vaksinator.' telah ditambahkan.')
            ->success()
            ->send();
    }
}
