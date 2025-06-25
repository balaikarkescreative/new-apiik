<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Models\IcvCekDokumen;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIcv extends EditRecord
{
    protected static string $resource = IcvResource::class;

    protected function afterSave(): void
    {
        $icv_dokumen = IcvCekDokumen::where('id_icv', $this->record->id)->first();
        if ($icv_dokumen) {
            $icv_dokumen->delete();
        }
    }

    protected function getRedirectUrl(): string
    {
        if (auth()->user()->hasRole('admin')) {
            return $this->getResource()::getUrl();
        } else {
            return $this->getResource()::getUrl('daftar-pengajuan');
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->hasRole('admin')),
        ];
    }
}
