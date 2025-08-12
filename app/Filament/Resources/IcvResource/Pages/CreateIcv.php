<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\ChecksOperationalHours;
use App\Filament\Resources\IcvResource;
use App\Filament\Resources\VaksinatorResource;
use App\Filament\Resources\SuratPersetujuanIcvResource;
use App\Models\Icv;
use App\Models\Vaksinator;
use App\Models\SuratPersetujuanIcv;
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

        // Jika user (bukan admin) belum punya laporan geotagging, redirect ke create laporan geotagging
        if (auth()->user()->hasRole('user') && !auth()->user()->laporanGeotagging) {
            Notification::make()
                ->title('Laporan Geotagging Belum Ada')
                ->body('Anda harus membuat laporan geotagging terlebih dahulu sebelum mengajukan ICV.')
                ->danger()
                ->persistent()
                ->send();
            redirect()->route('filament.admin.resources.laporan-geotagging.create');
            return;
        }

        // Jika user (bukan admin) belum punya link sosialisasi, redirect ke create link sosialisasi
        if (auth()->user()->hasRole('user') && !auth()->user()->linkSosialisasi) {
            Notification::make()
                ->title('Link Sosialisasi Belum Ada')
                ->body('Anda harus membuat link sosialisasi terlebih dahulu sebelum mengajukan ICV.')
                ->danger()
                ->persistent()
                ->send();
            redirect()->route('filament.admin.resources.link-sosialisasi.create');
            return;
        }

        // Jika user (bukan admin) belum punya pakta integritas, redirect ke create pakta integritas
        if (auth()->user()->hasRole('user') && !auth()->user()->paktaIntegritas) {
            Notification::make()
                ->title('Pakta Integritas Belum Ada')
                ->body('Anda harus mengupload pakta integritas terlebih dahulu sebelum mengajukan ICV.')
                ->danger()
                ->persistent()
                ->send();
            redirect()->route('filament.admin.resources.pakta-integritas.create');
            return;
        }

        if (!auth()->user()->hasRole('admin')) {
            $this->checkOperationalHoursWithRedirect('icv', IcvResource::getUrl('daftar-pengajuan'));
        }

        // Cek jika user belum memiliki vaksinator (untuk non-admin)
        if (!auth()->user()->hasRole('user')) {
            $this->checkVaksinatorRequirement(auth()->id());
        }

        // Cek jika user belum memiliki surat persetujuan ICV (untuk non-admin)
        // if (!auth()->user()->hasRole('admin')) {
        //     $this->checkSuratPersetujuanIcvRequirement(auth()->id());
        // }

        // if (auth()->user()->hasRole('user')) {
        //     $icvTertahan = \App\Models\Icv::where('user_id', auth()->id())
        //         // Jika belum ada icvCekDokumen, juga dianggap tertahan
        //         ->where(function ($q) {
        //             $q->whereDoesntHave('icvCekDokumen')
        //                 ->orWhereHas('icvCekDokumen', function ($qq) {
        //                     $qq->where('hasil_verif', 1);
        //                 });
        //         })
        //         ->where(function ($q) {
        //             $q->whereDoesntHave('icvSbbk')
        //                 ->orWhereHas('icvSbbk', function ($qq) {
        //                     $qq->whereNull('sinkarkes')->orWhere('sinkarkes', '!=', 'Sudah');
        //                 })
        //                 ->orWhereDoesntHave('icvBuktiBayar')
        //                 ->orWhereHas('icvBuktiBayar', function ($qq) {
        //                     $qq->whereNull('file_bukti_bayar')->orWhere('file_bukti_bayar', '');
        //                 });
        //         })
        //         ->exists();

        //     if ($icvTertahan) {
        //         Notification::make()
        //             ->title('Pengajuan ICV Ditolak')
        //             ->body('Anda masih memiliki pengajuan ICV yang masih di proses. Silakan selesaikan pengajuan tersebut sebelum mengajukan ICV baru.')
        //             ->danger()
        //             ->persistent()
        //             ->send();

        //         redirect()->to(IcvResource::getUrl('daftar-pengajuan'));
        //         return;
        //     }
        // }



        $hariIni = now()->format('Y-m-d');
        $kemarin = now()->subDay()->format('Y-m-d');
        // Fix: Change user_id to id_user for LaporanPenggunaanIcv table
        if (
            !LaporanPenggunaanIcv::where('id_user', auth()->id())->whereIn('tgl_pelaporan', [$hariIni, $kemarin])->exists() &&
            auth()->user()->hasRole('user') &&
            Icv::where('user_id', auth()->id())->exists()
        ) {

            Notification::make()
                ->title('Laporan Penggunaan ICV')
                ->body('Anda belum membuat laporan penggunaan ICV terbaru')
                ->warning()
                ->persistent()
                ->send();

            redirect()->route('filament.admin.resources.laporan-penggunaan-icv.create');
        }

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
        // if (!auth()->user()->hasRole('admin')) {
        //     if (!$izin || !$izin->izinPersetujuan) {
        //         Notification::make()
        //             ->title('Izin Tidak Ditemukan')
        //             ->body('Anda harus mengajukan izin terlebih dahulu sebelum membuat ICV.')
        //             ->danger()
        //             ->persistent()
        //             ->send();

        //         // Redirect ke halaman pengajuan izin
        //         redirect()->route('filament.admin.resources.izin.create');
        //     }
        // }
    }

    /**
     * Check if user has vaksinator data
     */
    private function checkVaksinatorRequirement(int $userId): void
    {
        $vaksinator = Vaksinator::where('id_akun', $userId)->aktif()->first();

        if (!$vaksinator) {
            Notification::make()
                ->title('Data Vaksinator Belum Lengkap')
                ->body('Anda harus melengkapi data vaksinator terlebih dahulu sebelum dapat mengajukan ICV. Silakan lengkapi data vaksinator di menu vaksinator.')
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lengkapi_vaksinator')
                        ->label('Lengkapi Data Vaksinator')
                        ->url(VaksinatorResource::getUrl())
                        ->button(),
                ])
                ->send();

            // Redirect ke halaman vaksinator
            redirect()->to(VaksinatorResource::getUrl());
            return;
        }

        // Cek juga apakah vaksinator masih aktif
        $this->validateVaksinatorStatus($vaksinator);
    }

    /**
     * Validate vaksinator status (izin praktek dan sertifikat)
     */
    private function validateVaksinatorStatus(Vaksinator $vaksinator): void
    {
        $warnings = [];
        $errors = [];

        // Cek status izin praktek
        if (!$vaksinator->masa_aktif_izin_praktek) {
            $errors[] = '• Masa aktif izin praktek belum diisi';
        } elseif ($vaksinator->masa_aktif_izin_praktek < now()) {
            $errors[] = '• Izin praktek sudah kadaluarsa (' . $vaksinator->masa_aktif_izin_praktek->format('d/m/Y') . ')';
        } elseif ($vaksinator->masa_aktif_izin_praktek <= now()->addDays(30)) {
            $warnings[] = '• Izin praktek akan habis dalam 30 hari (' . $vaksinator->masa_aktif_izin_praktek->format('d/m/Y') . ')';
        }

        // Cek status sertifikat vaksinator
        if (!$vaksinator->masa_aktif_sertifikat) {
            $errors[] = '• Masa aktif sertifikat vaksinator belum diisi';
        } elseif ($vaksinator->masa_aktif_sertifikat < now()) {
            $errors[] = '• Sertifikat vaksinator sudah kadaluarsa (' . $vaksinator->masa_aktif_sertifikat->format('d/m/Y') . ')';
        } elseif ($vaksinator->masa_aktif_sertifikat <= now()->addDays(30)) {
            $warnings[] = '• Sertifikat vaksinator akan habis dalam 30 hari (' . $vaksinator->masa_aktif_sertifikat->format('d/m/Y') . ')';
        }

        // Jika ada error, blokir pembuatan ICV
        if (!empty($errors)) {
            $errorMessage = "Data vaksinator Anda tidak valid untuk mengajukan ICV:\n\n" . implode("\n", $errors) . "\n\nSilakan perbarui data vaksinator terlebih dahulu.";

            Notification::make()
                ->title('Data Vaksinator Tidak Valid')
                ->body($errorMessage)
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('perbarui_vaksinator')
                        ->label('Perbarui Data Vaksinator')
                        ->url(VaksinatorResource::getUrl())
                        ->button(),
                ])
                ->send();

            redirect()->to(VaksinatorResource::getUrl());
            return;
        }

        // Jika ada warning, tampilkan peringatan tapi tetap izinkan
        if (!empty($warnings)) {
            $warningMessage = "Peringatan mengenai data vaksinator Anda:\n\n" . implode("\n", $warnings) . "\n\nDisarankan untuk memperbarui data vaksinator segera.";

            Notification::make()
                ->title('Peringatan Data Vaksinator')
                ->body($warningMessage)
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('perbarui_vaksinator')
                        ->label('Perbarui Data Vaksinator')
                        ->url(VaksinatorResource::getUrl())
                        ->button(),
                ])
                ->send();
        }
    }

    /**
     * Check if user has surat persetujuan ICV data
     */
    private function checkSuratPersetujuanIcvRequirement(int $userId): void
    {
        $suratPersetujuan = SuratPersetujuanIcv::where('id_akun', $userId)->masihBerlaku()->first();

        if (!$suratPersetujuan) {
            Notification::make()
                ->title('Surat Persetujuan ICV Belum Ada')
                ->body('Anda harus memiliki Surat Persetujuan ICV terlebih dahulu sebelum dapat mengajukan ICV. Silakan lengkapi surat persetujuan ICV melalui menu yang tersedia.')
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat_surat_persetujuan')
                        ->label('Lihat Surat Persetujuan ICV')
                        ->url(SuratPersetujuanIcvResource::getUrl())
                        ->button(),
                ])
                ->send();

            // Redirect ke halaman surat persetujuan ICV
            redirect()->to(SuratPersetujuanIcvResource::getUrl());
            return;
        }

        // Cek juga apakah surat persetujuan masih berlaku
        $this->validateSuratPersetujuanIcvStatus($suratPersetujuan);
    }

    /**
     * Validate surat persetujuan ICV status (masa berlaku)
     */
    private function validateSuratPersetujuanIcvStatus(SuratPersetujuanIcv $suratPersetujuan): void
    {
        $warnings = [];
        $errors = [];

        // Cek status masa berlaku surat persetujuan
        if (!$suratPersetujuan->masa_berlaku) {
            $errors[] = '• Masa berlaku surat persetujuan ICV belum diisi';
        } elseif ($suratPersetujuan->masa_berlaku < now()) {
            $errors[] = '• Surat persetujuan ICV sudah kadaluarsa (' . $suratPersetujuan->masa_berlaku->format('d/m/Y') . ')';
        } elseif ($suratPersetujuan->masa_berlaku <= now()->addDays(30)) {
            $warnings[] = '• Surat persetujuan ICV akan habis dalam 30 hari (' . $suratPersetujuan->masa_berlaku->format('d/m/Y') . ')';
        }

        // Validasi nomor surat
        if (!$suratPersetujuan->nomor_surat) {
            $errors[] = '• Nomor surat persetujuan ICV belum diisi';
        }

        // Validasi tanggal surat
        if (!$suratPersetujuan->tanggal_surat) {
            $errors[] = '• Tanggal surat persetujuan ICV belum diisi';
        }

        // Jika ada error, blokir pembuatan ICV
        if (!empty($errors)) {
            $errorMessage = "Surat persetujuan ICV Anda tidak valid untuk mengajukan ICV:\n\n" . implode("\n", $errors) . "\n\nSilakan lengkapi atau perbarui surat persetujuan ICV terlebih dahulu.";

            Notification::make()
                ->title('Surat Persetujuan ICV Tidak Valid')
                ->body($errorMessage)
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat_surat_persetujuan')
                        ->label('Lihat Surat Persetujuan ICV')
                        ->url(SuratPersetujuanIcvResource::getUrl())
                        ->button(),
                ])
                ->send();

            redirect()->to(SuratPersetujuanIcvResource::getUrl());
            return;
        }

        // Jika ada warning, tampilkan peringatan tapi tetap izinkan
        if (!empty($warnings)) {
            $warningMessage = "Peringatan mengenai surat persetujuan ICV Anda:\n\n" . implode("\n", $warnings) . "\n\nDisarankan untuk memperbarui surat persetujuan ICV segera.";

            Notification::make()
                ->title('Peringatan Surat Persetujuan ICV')
                ->body($warningMessage)
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat_surat_persetujuan')
                        ->label('Lihat Surat Persetujuan ICV')
                        ->url(SuratPersetujuanIcvResource::getUrl())
                        ->button(),
                ])
                ->send();
        }
    }

    protected function beforeCreate(): void
    {
        $data = $this->data;
        $userId = $data['user_id'] ?? auth()->id();

        // Validasi vaksinator untuk user yang dipilih (termasuk untuk admin)
        $this->validateUserVaksinator($userId);

        // Validasi surat persetujuan ICV untuk user yang dipilih (termasuk untuk admin)
        $this->validateUserSuratPersetujuanIcv($userId);
    }

    /**
     * Validate that selected user has valid vaksinator data
     */
    private function validateUserVaksinator(int $userId): void
    {
        $vaksinator = Vaksinator::where('id_akun', $userId)->aktif()->first();

        if (!$vaksinator) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Data Vaksinator Tidak Lengkap')
                ->body("{$userName} belum memiliki data vaksinator. Data vaksinator harus dilengkapi terlebih dahulu sebelum dapat mengajukan ICV.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validasi status vaksinator
        $errors = [];

        if (!$vaksinator->masa_aktif_izin_praktek || $vaksinator->masa_aktif_izin_praktek < now()) {
            $errors[] = 'Izin praktek tidak valid atau sudah kadaluarsa';
        }

        if (!$vaksinator->masa_aktif_sertifikat || $vaksinator->masa_aktif_sertifikat < now()) {
            $errors[] = 'Sertifikat vaksinator tidak valid atau sudah kadaluarsa';
        }

        if (!empty($errors)) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Data Vaksinator Tidak Valid')
                ->body("{$userName} memiliki masalah dengan data vaksinator:\n• " . implode("\n• ", $errors) . "\n\nSilakan perbarui data vaksinator terlebih dahulu.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Validate that selected user has valid surat persetujuan ICV
     */
    private function validateUserSuratPersetujuanIcv(int $userId): void
    {
        $suratPersetujuan = SuratPersetujuanIcv::where('id_akun', $userId)->masihBerlaku()->first();

        if (!$suratPersetujuan) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Surat Persetujuan ICV Tidak Ada')
                ->body("{$userName} belum memiliki Surat Persetujuan ICV. Surat persetujuan ICV harus ada terlebih dahulu sebelum dapat mengajukan ICV.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validasi status surat persetujuan
        $errors = [];

        if (!$suratPersetujuan->masa_berlaku || $suratPersetujuan->masa_berlaku < now()) {
            $errors[] = 'Surat persetujuan ICV tidak valid atau sudah kadaluarsa';
        }

        if (!$suratPersetujuan->nomor_surat) {
            $errors[] = 'Nomor surat persetujuan ICV belum diisi';
        }

        if (!$suratPersetujuan->tanggal_surat) {
            $errors[] = 'Tanggal surat persetujuan ICV belum diisi';
        }

        if (!empty($errors)) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Surat Persetujuan ICV Tidak Valid')
                ->body("{$userName} memiliki masalah dengan surat persetujuan ICV:\n• " . implode("\n• ", $errors) . "\n\nSilakan lengkapi atau perbarui surat persetujuan ICV terlebih dahulu.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
