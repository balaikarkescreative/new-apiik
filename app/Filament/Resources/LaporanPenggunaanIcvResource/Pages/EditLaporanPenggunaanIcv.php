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
        // Konversi data dari database ke format form
        $record = $this->getRecord();

        // Set jenis vaksin yang dipilih
        $data['jenis_vaksin'] = [$record->id_jenis_vaksin];

        // Set data untuk form nested
        $data['data'] = [
            $record->id_jenis_vaksin => [
                'jml_terbit_icv' => $record->jml_terbit_icv,
                'sisa_icv' => $record->sisa_icv,
                'jml_guna_vaksin' => $record->jml_guna_vaksin,
                'sisa_vaksin' => $record->sisa_vaksin,
                'keterangan' => $record->keterangan,
            ]
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
