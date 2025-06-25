<?php

namespace App\Filament\Resources\LaporanPenggunaanIcvResource\Pages;

use App\Filament\Resources\LaporanPenggunaanIcvResource;
use App\Models\LaporanPenggunaanIcv;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateLaporanPenggunaanIcv extends CreateRecord
{
    protected static string $resource = LaporanPenggunaanIcvResource::class;

    protected static bool $canCreateAnother = false;

    public function getRedirectUrl(): string
    {
        // Redirect ke halaman daftar laporan setelah berhasil membuat laporan baru
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        // Jika user bukan admin/verifikator2 dan id_user kosong, set ke user yang login
        if ((!$user->hasRole('admin') && !$user->hasRole('verifikator2')) || empty($data['id_user'])) {
            $data['id_user'] = $user->id;
        }

        // Handle multiple vaksin data
        if (isset($data['jenis_vaksin'], $data['data']) && is_array($data['jenis_vaksin'])) {
            $createdRecords = [];

            foreach ($data['jenis_vaksin'] as $vaksinId) {
                $vaksinData = $data['data'][$vaksinId] ?? [];
                $record = LaporanPenggunaanIcv::create([
                    'id_user' => $data['id_user'],
                    'id_jenis_vaksin' => $vaksinId,
                    'tgl_pelaporan' => $data['tgl_pelaporan'],
                    'jml_terbit_icv' => $vaksinData['jml_terbit_icv'] ?? 0,
                    'sisa_icv' => $vaksinData['sisa_icv'] ?? 0,
                    'jml_guna_vaksin' => $vaksinData['jml_guna_vaksin'] ?? 0,
                    'sisa_vaksin' => $vaksinData['sisa_vaksin'] ?? 0,
                    'keterangan' => $vaksinData['keterangan'] ?? null,
                ]);
                $createdRecords[] = $record;
            }

            // Return record pertama sebagai primary record
            if (!empty($createdRecords)) {
                Notification::make()
                    ->title('Laporan Berhasil Disimpan')
                    ->success()
                    ->body(count($createdRecords) . ' laporan penggunaan ICV berhasil dibuat.')
                    ->send();

                return $createdRecords[0];
            }
        }

        // Fallback untuk single record
        return LaporanPenggunaanIcv::create($data);
    }
}
