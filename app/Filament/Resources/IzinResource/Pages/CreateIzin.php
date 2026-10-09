<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\ChecksOperationalHours;
use App\Filament\Resources\IzinResource;
use App\Filament\Resources\SuratPersetujuanIcvResource;
use App\Filament\Resources\VaksinatorResource;
use App\Models\Izin;
use App\Models\SuratPersetujuanIcv;
use App\Models\Vaksinator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateIzin extends CreateRecord
{
    use ChecksOperationalHours;

    protected static string $resource = IzinResource::class;

    protected static bool $canCreateAnother = false;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('user'));
    }

    public function mount(): void
    {
        parent::mount();

        // Set form data awal berdasarkan parameter URL
        $type = request()->get('type');

        // Tentukan permit_type berdasarkan parameter
        $permitType = match ($type) {
            'baru' => 'izin_baru',
            'perpanjang' => 'izin_perpanjangan',
            default => 'izin_baru',
        };

        if (! auth()->user()->hasRole('admin')) {
            $this->checkOperationalHoursWithRedirect('izin', IzinResource::getUrl('daftar-pengajuan'), $permitType);
        }

        // Jika user diblokir, tidak boleh mengajukan Izin
        if (auth()->user()->is_blocked) {
            Notification::make()
                ->title('Akun Diblokir')
                ->body('Akun faskes Anda saat ini diblokir karena belum melakukan pengisian laporan harian, yang merupakan pelanggaran terhadap komitmen pada Nota Kesepahaman (MoU) yang telah disepakati. Selama akun diblokir, Anda tidak dapat membuat pengajuan Izin maupun ICV baru. Silakan lengkapi laporan atau hubungi Admin untuk bantuan lebih lanjut.')
                ->danger()
                ->persistent()
                ->send();
            redirect()->to(IzinResource::getUrl('daftar-pengajuan'));

            return;
        }

        if (! auth()->user()->hasRole('admin') && empty(auth()->user()->email)) {
            Notification::make()
                ->title('Profil Belum Lengkap')
                ->body('Anda harus melengkapi profil Anda sebelum mengajukan izin.')
                ->danger()
                ->persistent()
                ->send();

            // Redirect ke halaman edit profil
            redirect()->route('filament.admin.pages.edit-profil');
        }

        // Jika user (bukan admin) belum punya pakta integritas, redirect ke create pakta integritas
        if (auth()->user()->hasRole('user') && ! auth()->user()->paktaIntegritas) {
            Notification::make()
                ->title('Pakta Integritas Belum Ada')
                ->body('Anda harus mengupload pakta integritas terlebih dahulu sebelum mengajukan izin.')
                ->danger()
                ->persistent()
                ->send();
            redirect()->route('filament.admin.resources.pakta-integritas.create');

            return;
        }

        // Cek jika user belum memiliki vaksinator (untuk non-admin)
        if (! auth()->user()->hasRole('admin')) {
            $this->checkVaksinatorRequirement(auth()->id());
        }

        // Cek jika user belum memiliki surat persetujuan ICV (untuk non-admin)
        // if (!auth()->user()->hasRole('admin')) {
        //     $this->checkSuratPersetujuanIcvRequirement(auth()->id());
        // }

        // Cek jika ada izin yang masih diproses (untuk non-admin)
        if (! auth()->user()->hasRole('admin')) {
            $this->checkExistingActivePermits(auth()->id());
        }

        $formData = [
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ];

        // Set user_id
        if (auth()->user()->hasRole('admin')) {
            $formData['user_id'] = null; // Admin bisa pilih user
        } else {
            $formData['user_id'] = auth()->id(); // User biasa fixed ke dirinya
        }

        // Set kategori berdasarkan role dan parameter
        if (auth()->user()->hasRole('admin')) {
            // Admin bisa pilih kategori bebas, tapi tetap ada default
            $formData['kategori_izin'] = match ($type) {
                'baru' => 'Izin Baru',
                'perpanjang' => 'Perpanjang Izin',
                default => 'Izin Baru',
            };
        } else {
            // User biasa kategori fixed berdasarkan parameter
            $formData['kategori_izin'] = match ($type) {
                'baru' => 'Izin Baru',
                'perpanjang' => 'Perpanjang Izin',
                default => 'Izin Baru',
            };
        }

        $this->form->fill($formData);
    }

    /**
     * Check if user has surat persetujuan ICV data
     */
    private function checkSuratPersetujuanIcvRequirement(int $userId): void
    {
        $suratPersetujuan = SuratPersetujuanIcv::where('id_akun', $userId)->first();

        if (! $suratPersetujuan) {
            Notification::make()
                ->title('Surat Persetujuan ICV Belum Ada')
                ->body('Anda harus memiliki Surat Persetujuan ICV terlebih dahulu sebelum dapat mengajukan izin. Silakan ajukan permohonan ICV terlebih dahulu melalui menu ICV.')
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ajukan_icv')
                        ->label('Lihat Surat Persetujuan ICV')
                        ->url(SuratPersetujuanIcvResource::getUrl())
                        ->button(),
                ])
                ->send();

            // Redirect ke halaman pengajuan ICV
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
        if (! $suratPersetujuan->masa_berlaku) {
            $errors[] = '• Masa berlaku surat persetujuan ICV belum diisi';
        } elseif ($suratPersetujuan->masa_berlaku < now()) {
            $errors[] = '• Surat persetujuan ICV sudah kadaluarsa ('.$suratPersetujuan->masa_berlaku->format('d/m/Y').')';
        } elseif ($suratPersetujuan->masa_berlaku <= now()->addDays(30)) {
            $warnings[] = '• Surat persetujuan ICV akan habis dalam 30 hari ('.$suratPersetujuan->masa_berlaku->format('d/m/Y').')';
        }

        // Validasi nomor surat
        if (! $suratPersetujuan->nomor_surat) {
            $errors[] = '• Nomor surat persetujuan ICV belum diisi';
        }

        // Validasi tanggal surat
        if (! $suratPersetujuan->tanggal_surat) {
            $errors[] = '• Tanggal surat persetujuan ICV belum diisi';
        }

        // Jika ada error, blokir pembuatan izin
        if (! empty($errors)) {
            $errorMessage = "Surat persetujuan ICV Anda tidak valid untuk mengajukan izin:\n\n".implode("\n", $errors)."\n\nSilakan lengkapi atau perbarui surat persetujuan ICV terlebih dahulu.";

            Notification::make()
                ->title('Surat Persetujuan ICV Tidak Valid')
                ->body($errorMessage)
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat_icv')
                        ->label('Lihat Surat Persetujuan ICV')
                        ->url(SuratPersetujuanIcvResource::getUrl())
                        ->button(),
                ])
                ->send();

            redirect()->to(\App\Filament\Resources\IcvResource::getUrl('daftar-pengajuan'));

            return;
        }

        // Jika ada warning, tampilkan peringatan tapi tetap izinkan
        if (! empty($warnings)) {
            $warningMessage = "Peringatan mengenai surat persetujuan ICV Anda:\n\n".implode("\n", $warnings)."\n\nDisarankan untuk memperbarui surat persetujuan ICV segera.";

            Notification::make()
                ->title('Peringatan Surat Persetujuan ICV')
                ->body($warningMessage)
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ajukan_icv_baru')
                        ->label('Lihat Surat Persetujuan ICV')
                        ->url(SuratPersetujuanIcvResource::getUrl())
                        ->button(),
                ])
                ->send();
        }
    }

    /**
     * Check if user has vaksinator data
     */
    private function checkVaksinatorRequirement(int $userId): void
    {
        $vaksinator = Vaksinator::where('id_akun', $userId)->aktif()->first();

        if (! $vaksinator) {
            Notification::make()
                ->title('Data Vaksinator Belum Lengkap')
                ->body('Anda harus melengkapi data vaksinator terlebih dahulu sebelum dapat mengajukan izin. Silakan lengkapi data vaksinator di menu profil.')
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lengkapi_vaksinator')
                        ->label('Lengkapi Data Vaksinator')
                        ->url(VaksinatorResource::getUrl())
                        ->button(),
                ])
                ->send();

            // Redirect ke halaman edit profil dengan anchor ke section vaksinator
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
        if (! $vaksinator->masa_aktif_izin_praktek) {
            $errors[] = '• Masa aktif izin praktek belum diisi';
        } elseif ($vaksinator->masa_aktif_izin_praktek < now()) {
            $errors[] = '• Izin praktek sudah kadaluarsa ('.$vaksinator->masa_aktif_izin_praktek->format('d/m/Y').')';
        } elseif ($vaksinator->masa_aktif_izin_praktek <= now()->addDays(30)) {
            $warnings[] = '• Izin praktek akan habis dalam 30 hari ('.$vaksinator->masa_aktif_izin_praktek->format('d/m/Y').')';
        }

        // Cek dokumen izin praktek
        if (! $vaksinator->dokumen_izin_praktek) {
            $errors[] = '• Dokumen izin praktek belum diupload';
        }

        // Cek status sertifikat vaksinator
        if (! $vaksinator->masa_aktif_sertifikat) {
            $errors[] = '• Masa aktif sertifikat vaksinator belum diisi';
        } elseif ($vaksinator->masa_aktif_sertifikat < now()) {
            $errors[] = '• Sertifikat vaksinator sudah kadaluarsa ('.$vaksinator->masa_aktif_sertifikat->format('d/m/Y').')';
        } elseif ($vaksinator->masa_aktif_sertifikat <= now()->addDays(30)) {
            $warnings[] = '• Sertifikat vaksinator akan habis dalam 30 hari ('.$vaksinator->masa_aktif_sertifikat->format('d/m/Y').')';
        }

        // Cek dokumen sertifikat vaksinator
        if (! $vaksinator->dokumen_sertifikat_vaksinator) {
            $errors[] = '• Dokumen sertifikat vaksinator belum diupload';
        }

        // Jika ada error, blokir pembuatan izin
        if (! empty($errors)) {
            $errorMessage = "Data vaksinator Anda tidak valid untuk mengajukan izin:\n\n".implode("\n", $errors)."\n\nSilakan perbarui data vaksinator terlebih dahulu.";

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
        if (! empty($warnings)) {
            $warningMessage = "Peringatan mengenai data vaksinator Anda:\n\n".implode("\n", $warnings)."\n\nDisarankan untuk memperbarui data vaksinator segera.";

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
     * Check if user has any active/pending permits
     */
    private function checkExistingActivePermits(int $userId): void
    {
        // Cek pengajuan terakhir user
        $latestPermit = Izin::where('user_id', $userId)
            ->with(['izinPersetujuan'])
            ->latest('tanggal_pengajuan')
            ->first();

        // Jika pengajuan terakhir sudah memiliki persetujuan, izinkan perpanjangan
        if ($latestPermit && $latestPermit->izinPersetujuan && $latestPermit->izinPersetujuan->file_persetujuan) {
            // User bisa mengajukan perpanjangan karena izin terakhir sudah selesai
            return;
        }

        // Jika belum, cek apakah ada pengajuan yang masih aktif/belum selesai
        $activePermits = Izin::where('user_id', $userId)
            ->with(['izinPersetujuan', 'izinVisitasi', 'izinDokumen'])
            ->where(function ($query) {
                // Izin yang belum memiliki Persetujuan atau Persetujuan belum selesai
                $query->whereDoesntHave('izinPersetujuan')
                    ->orWhereHas('izinPersetujuan', function ($subQuery) {
                        $subQuery->whereNull('file_persetujuan');
                    });
            })
            // Exclude permits with permanently rejected visitasi or verification
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    // Exclude rejected documents (hasil = 3)
                    $subQuery->whereDoesntHave('izinDokumen')
                        ->orWhereHas('izinDokumen', function ($dokQuery) {
                            $dokQuery->where('hasil', '!=', 3);
                        });
                })
                    ->where(function ($subQuery) {
                        // Exclude permanently rejected visitasi
                        $subQuery->whereDoesntHave('izinVisitasi')
                            ->orWhereHas('izinVisitasi', function ($visQuery) {
                                $visQuery->where('status', '!=', 'Tidak memenuhi syarat');
                            });
                    });
            })
            ->get();

        if ($activePermits->isNotEmpty()) {
            $permitDetails = $activePermits->map(function ($permit) {
                $status = $this->getPermitStatus($permit);

                return "• {$permit->kategori_izin} - {$status} (Pengajuan: {$permit->tanggal_pengajuan->format('d/m/Y')})";
            })->join("\n");

            Notification::make()
                ->title('Tidak Dapat Mengajukan Izin Baru')
                ->body("Anda masih memiliki pengajuan izin yang belum selesai:\n\n{$permitDetails}\n\nSilakan selesaikan pengajuan yang ada terlebih dahulu sebelum mengajukan izin baru.")
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat_pengajuan')
                        ->label('Lihat Pengajuan')
                        ->url(IzinResource::getUrl('daftar-pengajuan'))
                        ->button(),
                ])
                ->send();

            // Redirect ke halaman daftar pengajuan
            redirect()->to(IzinResource::getUrl('daftar-pengajuan'));
        }
    }

    /**
     * Get human-readable permit status
     */
    private function getPermitStatus($permit): string
    {
        // Cek apakah sudah ada PKS
        if ($permit->izinDokumenPks && $permit->izinDokumenPks->dokumen_pks_bkk) {
            return 'PKS Tersedia - Menunggu Persetujuan';
        }

        // Cek apakah sudah dijadwalkan visitasi
        if ($permit->izinDokumenPks && $permit->izinDokumenPks->tanggal_visitasi) {
            return 'Visitasi Dijadwalkan';
        }

        // Cek apakah dokumen sudah diverifikasi
        if ($permit->izinDokumen) {
            return match ($permit->izinDokumen->hasil) {
                1 => 'Berkas Lengkap',
                2 => 'Berkas Tidak Lengkap',
                default => 'Menunggu Verifikasi'
            };
        }

        return 'Dokumen Diajukan';
    }

    protected function afterCreate(): void
    {
        IzinResource::sendEmailNotification($this->record);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set user_id jika belum ada (untuk user biasa)
        if (! isset($data['user_id']) || ! $data['user_id']) {
            $data['user_id'] = auth()->id();
        }

        // Set tanggal_pengajuan jika belum ada
        if (! isset($data['tanggal_pengajuan']) || ! $data['tanggal_pengajuan']) {
            $data['tanggal_pengajuan'] = now();
        }

        // Set date_updated
        $data['date_updated'] = now();

        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->data;
        $userId = $data['user_id'] ?? auth()->id();

        // Validasi surat persetujuan ICV untuk user yang dipilih (termasuk untuk admin)
        // $this->validateUserSuratPersetujuanIcv($userId);

        // Validasi vaksinator untuk user yang dipilih (termasuk untuk admin)
        $this->validateUserVaksinator($userId);

        // Double-check for active permits before creating (security measure)
        $this->validateNoActivePermits($userId);

        // cek jika kategori izin adalah "Perpanjang Izin" dan user belum memiliki izin yang sudah ada
        // if ($data['kategori_izin'] === 'Perpanjang Izin') {
        //     $this->validateExtensionPermitRequirements($userId);
        // }

        // // Cek jika kategori izin adalah "Izin Baru"
        // if ($data['kategori_izin'] === 'Izin Baru') {
        //     $this->validateNewPermitRequirements($userId);
        // }
    }

    /**
     * Validate that selected user has valid surat persetujuan ICV
     */
    private function validateUserSuratPersetujuanIcv(int $userId): void
    {
        $suratPersetujuan = SuratPersetujuanIcv::where('id_akun', $userId)->first();

        if (! $suratPersetujuan) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Surat Persetujuan ICV Tidak Ada')
                ->body("{$userName} belum memiliki Surat Persetujuan ICV. Surat persetujuan ICV harus ada terlebih dahulu sebelum dapat mengajukan izin.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validasi status surat persetujuan
        $errors = [];

        if (! $suratPersetujuan->masa_berlaku || $suratPersetujuan->masa_berlaku < now()) {
            $errors[] = 'Surat persetujuan ICV tidak valid atau sudah kadaluarsa';
        }

        if (! $suratPersetujuan->nomor_surat) {
            $errors[] = 'Nomor surat persetujuan ICV belum diisi';
        }

        if (! $suratPersetujuan->tanggal_surat) {
            $errors[] = 'Tanggal surat persetujuan ICV belum diisi';
        }

        if (! empty($errors)) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Surat Persetujuan ICV Tidak Valid')
                ->body("{$userName} memiliki masalah dengan surat persetujuan ICV:\n• ".implode("\n• ", $errors)."\n\nSilakan lengkapi atau perbarui surat persetujuan ICV terlebih dahulu.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Validate that selected user has valid vaksinator data
     */
    private function validateUserVaksinator(int $userId): void
    {
        $vaksinator = Vaksinator::where('id_akun', $userId)->aktif()->first();

        if (! $vaksinator) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Data Vaksinator Tidak Lengkap')
                ->body("{$userName} belum memiliki data vaksinator. Data vaksinator harus dilengkapi terlebih dahulu sebelum dapat mengajukan izin.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validasi status vaksinator
        $errors = [];

        if (! $vaksinator->masa_aktif_izin_praktek || $vaksinator->masa_aktif_izin_praktek < now()) {
            $errors[] = 'Izin praktek tidak valid atau sudah kadaluarsa';
        }

        if (! $vaksinator->dokumen_izin_praktek) {
            $errors[] = 'Dokumen izin praktek belum diupload';
        }

        if (! $vaksinator->masa_aktif_sertifikat || $vaksinator->masa_aktif_sertifikat < now()) {
            $errors[] = 'Sertifikat vaksinator tidak valid atau sudah kadaluarsa';
        }

        if (! $vaksinator->dokumen_sertifikat_vaksinator) {
            $errors[] = 'Dokumen sertifikat vaksinator belum diupload';
        }

        if (! empty($errors)) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : 'User';

            Notification::make()
                ->title('Data Vaksinator Tidak Valid')
                ->body("{$userName} memiliki masalah dengan data vaksinator:\n• ".implode("\n• ", $errors)."\n\nSilakan perbarui data vaksinator terlebih dahulu.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Validate that user has no active permits
     */
    private function validateNoActivePermits(int $userId): void
    {
        $existingActivePermit = Izin::where('user_id', $userId)
            ->with(['izinPersetujuan', 'izinVisitasi', 'izinDokumen'])
            ->where(function ($query) {
                $query->whereDoesntHave('izinPersetujuan')
                    ->orWhereHas('izinPersetujuan', function ($subQuery) {
                        $subQuery->whereNull('file_persetujuan');
                    });
            })
            // Exclude permits with permanently rejected visitasi or verification
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    // Exclude rejected documents (hasil = 3)
                    $subQuery->whereDoesntHave('izinDokumen')
                        ->orWhereHas('izinDokumen', function ($dokQuery) {
                            $dokQuery->where('hasil', '!=', 3);
                        });
                })
                    ->where(function ($subQuery) {
                        // Exclude permanently rejected visitasi
                        $subQuery->whereDoesntHave('izinVisitasi')
                            ->orWhereHas('izinVisitasi', function ($visQuery) {
                                $visQuery->where('status', '!=', 'Tidak memenuhi syarat');
                            });
                    });
            })
            ->first();

        if ($existingActivePermit) {
            $status = $this->getPermitStatus($existingActivePermit);

            Notification::make()
                ->title('Gagal Membuat Izin')
                ->body("User masih memiliki pengajuan izin yang belum selesai:\n{$existingActivePermit->kategori_izin} - {$status}\n\nSilakan selesaikan pengajuan yang ada terlebih dahulu.")
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat_pengajuan')
                        ->label('Lihat Pengajuan')
                        ->url(IzinResource::getUrl('daftar-pengajuan'))
                        ->button(),
                ])
                ->send();

            $this->halt();
        }
    }

    /**
     * Validate requirements for permit extension
     */
    private function validateExtensionPermitRequirements(int $userId): void
    {
        // Cek apakah user memiliki izin baru yang sudah selesai (ada PKS)
        $completedNewPermit = Izin::where('user_id', $userId)
            ->where('kategori_izin', 'Izin Baru')
            ->whereHas('IzinPersetujuan', function ($query) {
                $query->whereNotNull('file_persetujuan');
            })
            ->exists();

        if (! $completedNewPermit) {
            Notification::make()
                ->title('Gagal Membuat Perpanjangan Izin')
                ->body('User belum memiliki Izin Baru yang selesai untuk dapat diperpanjang. Izin baru harus selesai sepenuhnya (termasuk PKS) sebelum dapat diperpanjang.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Validate requirements for new permit
     */
    private function validateNewPermitRequirements(int $userId): void
    {
        // Cek apakah user sudah pernah mengajukan izin baru
        $existingNewPermit = Izin::where('user_id', $userId)
            ->where('kategori_izin', 'Izin Baru')
            ->whereHas('izinDokumenPks', function ($query) {
                $query->whereNotNull('dokumen_pks_bkk');
            })
            ->exists();

        // Hanya validasi untuk non-admin atau jika admin membuat untuk user yang sudah ada izin baru
        if ($existingNewPermit && (auth()->user()->name !== 'Admin' || $userId !== auth()->id())) {
            $message = auth()->user()->name === 'Admin'
                ? 'User tersebut sudah pernah mengajukan Izin Baru sebelumnya.'
                : 'Anda sudah pernah mengajukan Izin Baru sebelumnya. Setiap user hanya dapat mengajukan Izin Baru sekali. Silakan gunakan fitur Perpanjang Izin untuk memperpanjang izin yang sudah ada.';

            Notification::make()
                ->title('Gagal Membuat Izin')
                ->body($message)
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        // return IzinResource::getUrl('daftar-pengajuan');
        if (auth()->user()->hasRole('admin')) {
            return IzinResource::getUrl('index');
        } else {
            return IzinResource::getUrl('daftar-pengajuan');
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Izin berhasil diajukan';
    }

    public function getTitle(): string
    {
        $type = request()->get('type');

        return match ($type) {
            'baru' => 'Buat Izin Baru',
            'perpanjang' => 'Perpanjang Izin',
            default => 'Buat Pengajuan Izin',
        };
    }
}
