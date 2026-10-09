<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Models\IzinDokumen;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditIzin extends EditRecord
{
    protected static string $resource = IzinResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('user'));
    }

    protected function afterSave(): void
    {
        $cek_izin = IzinDokumen::where('izin_id', $this->record->id)->first();

        if ($cek_izin) {
            $cek_izin = $cek_izin->delete();
        }

        if ($this->data['tanggal_perbaikan']) {
            $this->record->tanggal_perbaikan = $this->data['tanggal_perbaikan'];
            $this->record->save();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user->is_blocked) {
            Notification::make()
                ->title('Akun Diblokir')
                ->body('Akun faskes Anda saat ini diblokir karena belum melakukan pengisian laporan harian, yang merupakan pelanggaran terhadap komitmen pada Nota Kesepahaman (MoU) yang telah disepakati. Selama akun diblokir, Anda tidak dapat membuat pengajuan Izin maupun ICV baru. Silakan lengkapi laporan atau hubungi Admin untuk bantuan lebih lanjut.')
                ->danger()
                ->persistent()
                ->send();

            redirect()->to(IzinResource::getUrl('daftar-pengajuan'));
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
}
