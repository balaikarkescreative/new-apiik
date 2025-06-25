<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\ChecksOperationalHours;
use App\Filament\Resources\IcvResource;
use App\Models\Icv;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Izin;
use Filament\Notifications\Notification;
use App\Models\LaporanPenggunaanIcv;
use App\Filament\Resources\LaporanPenggunaanIcvResource;

class CreateIcv extends CreateRecord
{
    use ChecksOperationalHours;
    protected static string $resource = IcvResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {


        if (auth()->user()->hasRole('admin')) {
            return $this->getResource()::getUrl();
        }
        return $this->getResource()::getUrl('daftar-pengajuan');
    }

    public function mount(): void
    {
        parent::mount();

        if (!auth()->user()->hasRole('admin')) {
            $this->checkOperationalHoursWithRedirect('icv', IcvResource::getUrl('daftar-pengajuan'));
        }

        // Fix: Change user_id to id_user for LaporanPenggunaanIcv table
        if (!LaporanPenggunaanIcv::where('id_user', auth()->id())->where('tgl_pelaporan', now()->format('Y-m-d'))->exists() &&
            auth()->user()->hasRole('user') &&
            Icv::where('user_id', auth()->id())->exists()) {

            Notification::make()
                ->title('Laporan Penggunaan ICV')
                ->body('Anda belum membuat laporan penggunaan ICV untuk hari ini.')
                ->warning()
                ->persistent()
                ->send();

            redirect()->route('filament.admin.resources.laporan-penggunaan-icv.create');
        }




        // Commented out working hours restriction
        // if ((now()->hour < 8 || now()->hour >= 15) && !auth()->user()->hasRole('admin')) {
        //     Notification::make()
        //         ->title('Pengajuan ICV')
        //         ->body('Pengajuan ICV hanya dapat dilakukan pada jam kerja (08:00 - 15:00).')
        //         ->warning()
        //         ->persistent()
        //         ->send();
        //     redirect()->route('filament.admin.resources.icv.daftar-pengajuan');
        // }

        $izin = null;

        if (!auth()->user()->hasRole('admin')) {
            $izin = Izin::with('user', 'izinPersetujuan')
                ->where('user_id', auth()->id())
                ->first();
        } else {
            // Jika admin, ambil izin dari user yang dipilih
            $userId = request()->get('user_id');
            if ($userId) {
                $izin = Izin::with('user', 'izinPersetujuan')
                    ->where('user_id', $userId)
                    ->first();
            }
        }

        // Validasi izin untuk non-admin
        if (!auth()->user()->hasRole('admin')) {
            if (!$izin || !$izin->izinPersetujuan) {
                Notification::make()
                    ->title('Izin Tidak Ditemukan')
                    ->body('Anda harus mengajukan izin terlebih dahulu sebelum membuat ICV.')
                    ->danger()
                    ->persistent()
                    ->send();

                // Redirect ke halaman pengajuan izin
                redirect()->route('filament.admin.resources.izin.create');
            }
        }
    }
}
