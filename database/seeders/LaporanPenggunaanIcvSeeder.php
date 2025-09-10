<?php

namespace Database\Seeders;

use App\Models\LaporanPenggunaanIcv;
use App\Models\User;
use App\Models\Vaksin;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LaporanPenggunaanIcvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_laporan_penggunaan_icv.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $laporanData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $laporanData = $item['data'];
                break;
            }
        }

        if (! $laporanData) {
            $this->command->error('No laporan penggunaan ICV data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($laporanData).' laporan penggunaan ICV records...');

        $processed = 0;
        $skipped = 0;

        foreach ($laporanData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && LaporanPenggunaanIcv::where('id', $record['id'])->exists()) {
                $this->command->warn("LaporanPenggunaanIcv with ID {$record['id']} already exists. Skipping...");
                $skipped++;

                continue;
            }

            // Skip records with invalid data
            if (empty($record['id_klinik']) || $record['id_klinik'] == '0' ||
                empty($record['tgl_pelaporan']) || $record['tgl_pelaporan'] === '0000-00-00') {
                $this->command->warn("Skipping record ID {$record['id']} - invalid id_klinik or tgl_pelaporan");
                $skipped++;

                continue;
            }

            // Skip if id_jenis_vaksin is null or '0'
            if (empty($record['id_jenis_vaksin']) || $record['id_jenis_vaksin'] === '0') {
                $this->command->warn("Skipping record ID {$record['id']} - id_jenis_vaksin is null or 0");
                $skipped++;

                continue;
            }

            // Find User by id_klinik (assuming id_klinik maps to user id)
            $user = User::find($record['id_klinik']);
            if (! $user) {
                $this->command->warn("User with ID {$record['id_klinik']} not found for laporan ID {$record['id']}. Skipping...");
                $skipped++;

                continue;
            }

            // Find Vaksin by id_jenis_vaksin (now required)
            $vaksin = Vaksin::find($record['id_jenis_vaksin']);
            if (! $vaksin) {
                $this->command->warn("Vaksin with ID {$record['id_jenis_vaksin']} not found for laporan ID {$record['id']}. Skipping...");
                $skipped++;

                continue;
            }

            // Prepare laporan data
            $laporanDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_user' => $user->id,
                'id_jenis_vaksin' => $vaksin->id,
                'tgl_pelaporan' => $this->parseDate($record['tgl_pelaporan']),
                'jml_terbit_icv' => (int) ($record['jml_terbit_icv'] ?? 0),
                'sisa_icv' => (int) ($record['sisa_icv'] ?? 0),
                'jml_guna_vaksin' => (int) ($record['jml_guna_vaksin'] ?? 0),
                'sisa_vaksin' => (int) ($record['sisa_vaksin'] ?? 0),
                'keterangan' => $record['keterangan'] ?? '',
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create LaporanPenggunaanIcv record
            $laporan = LaporanPenggunaanIcv::create($laporanDataToInsert);

            $this->command->info("Created LaporanPenggunaanIcv ID: {$laporan->id} - {$laporan->tgl_pelaporan} (User ID: {$user->id}, Vaksin ID: {$vaksin->id})");
            $processed++;
        }

        $this->command->info('LaporanPenggunaanIcvSeeder completed successfully!');
        $this->command->info("Processed: {$processed} records");
        $this->command->info("Skipped: {$skipped} records");
    }

    /**
     * Parse date string to proper format
     */
    private function parseDate($dateString)
    {
        if (! $dateString || $dateString === '0000-00-00' || $dateString === '0') {
            return null;
        }

        try {
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->command->warn("Invalid date format: {$dateString}");

            return null;
        }
    }

    /**
     * Parse datetime string to proper format
     */
    private function parseDateTime($datetimeString)
    {
        if (! $datetimeString || $datetimeString === '0000-00-00 00:00:00') {
            return now();
        }

        try {
            return Carbon::parse($datetimeString);
        } catch (\Exception $e) {
            $this->command->warn("Invalid datetime format: {$datetimeString}");

            return now();
        }
    }
}
