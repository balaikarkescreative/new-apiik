<?php

namespace App\Filament\Resources\LaporanPenggunaanIcvResource\Pages;

use App\Filament\Resources\LaporanPenggunaanIcvResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanPenggunaanIcv extends EditRecord
{
    protected static string $resource = LaporanPenggunaanIcvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Use the data parameter instead of getRecord() since the record might not be initialized yet
        // The $data already contains the record data from the database

        // Set jenis vaksin yang dipilih
        $data['jenis_vaksin'] = [$data['id_jenis_vaksin']];

        // Set data untuk form nested - restructure the flat database data into nested format
        $data['data'] = [
            $data['id_jenis_vaksin'] => [
                'jml_terbit_icv' => $data['jml_terbit_icv'] ?? 0,
                'sisa_icv' => $data['sisa_icv'] ?? 0,
                'jml_guna_vaksin' => $data['jml_guna_vaksin'] ?? 0,
                'sisa_vaksin' => $data['sisa_vaksin'] ?? 0,
                'keterangan' => $data['keterangan'] ?? null,
            ],
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Konversi data dari form ke format database
        $vaksinId = $data['jenis_vaksin'][0] ?? null;

        if ($vaksinId && isset($data['data'][$vaksinId])) {
            $vaksinData = $data['data'][$vaksinId];

            return [
                'tgl_pelaporan' => $data['tgl_pelaporan'],
                'id_user' => auth()->id(),
                'id_jenis_vaksin' => $vaksinId,
                'jml_terbit_icv' => $vaksinData['jml_terbit_icv'],
                'sisa_icv' => $vaksinData['sisa_icv'],
                'jml_guna_vaksin' => $vaksinData['jml_guna_vaksin'],
                'sisa_vaksin' => $vaksinData['sisa_vaksin'],
                'keterangan' => $vaksinData['keterangan'] ?? null,
            ];
        }

        return $data;
    }
}
