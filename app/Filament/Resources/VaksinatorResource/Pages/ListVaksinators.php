<?php

namespace App\Filament\Resources\VaksinatorResource\Pages;

use App\Filament\Resources\VaksinatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListVaksinators extends ListRecords
{
    protected static string $resource = VaksinatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Vaksinator'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Notifikasi untuk vaksinator yang akan kadaluarsa
        // $akanKadaluarsa = $this->getResource()::getModel()::akanHabis(30)->count();

        // if ($akanKadaluarsa > 0) {
        //     Notification::make()
        //         ->title('Perhatian!')
        //         ->body("Ada {$akanKadaluarsa} vaksinator yang izin/sertifikatnya akan habis dalam 30 hari.")
        //         ->warning()
        //         ->persistent()
        //         ->send();
        // }
    }
}
