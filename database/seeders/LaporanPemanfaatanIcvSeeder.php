<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LaporanPemanfaatanIcv;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LaporanPemanfaatanIcvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_laporan_pemanfaatan_icv.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        // Temukan array data di struktur JSON
        $laporanData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $laporanData = $item['data'];
                break;
            }
        }

        if (!$laporanData) {
            $this->command->error('No laporan pemanfaatan ICV data found in JSON file');
            return;
        }

        $this->command->info('Processing ' . count($laporanData) . ' laporan pemanfaatan ICV records...');

        foreach ($laporanData as $record) {
            // Cek apakah user dengan id_klinik ada
            if (!\App\Models\User::where('id', $record['id_klinik'])->exists()) {
                $this->command->warn("User dengan id_klinik {$record['id_klinik']} tidak ditemukan. Data id={$record['id']} dilewati.");
                continue;
            }

            // Buat folder jika belum ada
            $folderPath = public_path('laporan-pemanfaatan-icv');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Insert data dengan id
            LaporanPemanfaatanIcv::create([
                'id' => $record['id'],
                'id_klinik' => $record['id_klinik'],
                'tanggal' => $record['tanggal'],
                'judul_laporan' => $record['judul_laporan'],
                'file' => 'laporan-pemanfaatan-icv/' . ltrim($record['file'], '/'),
            ]);
        }

        $this->command->info('LaporanPemanfaatanIcvSeeder completed successfully!');
    }
}
