<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\ChecksOperationalHours;
use App\Filament\Resources\IzinResource;
use Filament\Actions;
use App\Models\Izin;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateIzin extends CreateRecord
{
    use ChecksOperationalHours;
    protected static string $resource = IzinResource::class;

    protected static bool $canCreateAnother = false;


    public function mount(): void
    {
        parent::mount();

        if (!auth()->user()->hasRole('admin')) {
            $this->checkOperationalHoursWithRedirect('izin', IzinResource::getUrl('daftar-pengajuan'));
        }

        if (!auth()->user()->hasRole('admin') && empty(auth()->user()->email)) {
            Notification::make()
                ->title('Profil Belum Lengkap')
                ->body('Anda harus melengkapi profil Anda sebelum mengajukan izin.')
                ->danger()
                ->persistent()
                ->send();

            // Redirect ke halaman edit profil
            redirect()->route('filament.admin.pages.edit-profil');
        }

        // Cek jika ada izin yang masih diproses (untuk non-admin)
        if (!auth()->user()->hasRole('admin')) {
            $this->checkExistingActivePermits(auth()->id());
        }

        // Set form data awal berdasarkan parameter URL
        $type = request()->get('type');

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
     * Check if user has any active/pending permits
     */
    private function checkExistingActivePermits(int $userId): void
    {
        // Cek izin yang masih dalam proses (belum ada dokumen_pks_bkk)
        $activePermits = Izin::where('user_id', $userId)
            ->with(['izinDokumenPks', 'izinPersetujuan'])
            ->where(function ($query) {
                // Izin yang belum memiliki PKS atau PKS belum selesai
                $query->whereDoesntHave('izinDokumenPks')
                      ->orWhereHas('izinDokumenPks', function ($subQuery) {
                          $subQuery->whereNull('dokumen_pks_bkk');
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
            return 'Selesai';
        }

        // Cek apakah sudah disetujui
        if ($permit->izinPersetujuan && $permit->izinPersetujuan->status == 1) {
            return 'Disetujui - Menunggu PKS';
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
        if (!isset($data['user_id']) || !$data['user_id']) {
            $data['user_id'] = auth()->id();
        }

        // Set tanggal_pengajuan jika belum ada
        if (!isset($data['tanggal_pengajuan']) || !$data['tanggal_pengajuan']) {
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

        // Double-check for active permits before creating (security measure)
        $this->validateNoActivePermits($userId);

        // cek jika kategori izin adalah "Perpanjang Izin" dan user belum memiliki izin yang sudah ada
        if ($data['kategori_izin'] === 'Perpanjang Izin') {
            $this->validateExtensionPermitRequirements($userId);
        }

        // Cek jika kategori izin adalah "Izin Baru"
        if ($data['kategori_izin'] === 'Izin Baru') {
            $this->validateNewPermitRequirements($userId);
        }
    }

    /**
     * Validate that user has no active permits
     */
    private function validateNoActivePermits(int $userId): void
    {
        $existingActivePermit = Izin::where('user_id', $userId)
            ->with(['izinDokumenPks'])
            ->where(function ($query) {
                $query->whereDoesntHave('izinDokumenPks')
                      ->orWhereHas('izinDokumenPks', function ($subQuery) {
                          $subQuery->whereNull('dokumen_pks_bkk');
                      });
            })
            ->first();

        if ($existingActivePermit) {
            $status = $this->getPermitStatus($existingActivePermit);

            Notification::make()
                ->title('Gagal Membuat Izin')
                ->body("Anda masih memiliki pengajuan izin yang belum selesai:\n{$existingActivePermit->kategori_izin} - {$status}\n\nSilakan selesaikan pengajuan yang ada terlebih dahulu.")
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
            ->whereHas('izinDokumenPks', function ($query) {
                $query->whereNotNull('dokumen_pks_bkk');
            })
            ->exists();

        if (!$completedNewPermit) {
            Notification::make()
                ->title('Gagal Membuat Perpanjangan Izin')
                ->body('Anda belum memiliki Izin Baru yang selesai untuk dapat diperpanjang. Izin baru harus selesai sepenuhnya (termasuk PKS) sebelum dapat diperpanjang.')
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
