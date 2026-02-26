<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;


class EditIcv extends EditRecord
{
    protected static string $resource = IcvResource::class;

    protected function afterSave(): void
    {
        $icv_dokumen = \App\Models\IcvCekDokumen::where('id_icv', $this->record->id)->first();
        if ($icv_dokumen) {
            $icv_dokumen->delete();
        }

        // Set tanggal_perbaikan jika ada di data
        if (! empty($this->data['tanggal_perbaikan'])) {
            $this->record->tanggal_perbaikan = $this->data['tanggal_perbaikan'];
            $this->record->save();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        if ($this->record->user->is_blocked) {
            Notification::make()
                ->title('Akun Diblokir')
                ->body('Akun faskes Anda sedang diblokir oleh sistem. Anda tidak dapat membuat pengajuan Izin maupun ICV baru saat ini. Silakan hubungi admin untuk informasi lebih lanjut.')
                ->danger()
                ->persistent()
                ->send();

            redirect()->to(IcvResource::getUrl('daftar-pengajuan'));
        }

        $type = request()->get('type');
        if ($type === 'perbaikan') {
            $data['tanggal_perbaikan'] = now();
        }

        return $data;
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
            Actions\DeleteAction::make()->visible(fn() => auth()->user()->hasRole('admin')),
        ];
    }
}
