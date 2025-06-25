<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IcvCekDokumen;
use App\Models\Icv;
use Carbon\Carbon;

class IcvCekDokumenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_cek_dokumen.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $cekDokumenData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $cekDokumenData = $item['data'];
                break;
            }
        }

        if (!$cekDokumenData) {
            $this->command->error('No cek dokumen data found in JSON file');
            return;
        }

        $this->command->info('Processing ' . count($cekDokumenData) . ' cek dokumen records...');

        foreach ($cekDokumenData as $record) {
            // Skip if ID already exists
            if (!empty($record['id']) && IcvCekDokumen::where('id', $record['id'])->exists()) {
                $this->command->warn("IcvCekDokumen with ID {$record['id']} already exists. Skipping...");
                continue;
            }

            // Skip if id_icv is 0 or empty
            if (empty($record['id_icv']) || $record['id_icv'] == '0') {
                $this->command->warn("Skipping cek dokumen ID {$record['id']} - id_icv is 0 or empty");
                continue;
            }

            // Find ICV by id_icv
            $icv = Icv::find($record['id_icv']);
            if (!$icv) {
                $this->command->warn("ICV with ID {$record['id_icv']} not found for cek dokumen ID {$record['id']}. Skipping...");
                continue;
            }

            // Prepare cek dokumen data
            $cekDokumenDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_icv' => $icv->id,
                'hasil_verif' => $this->mapHasilVerif($record['hasil_verif'] ?? ''),
                'stok_vaksin' => (int)($record['stok_vaksin'] ?? 0),
                'jumlah_icv' => (int)($record['jumlah_icv'] ?? 0),
                'catatan' => $record['catatan'] ?? '',
                'status' => $this->mapStatus($record['hasil_verif'] ?? ''),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IcvCekDokumen record
            $cekDokumen = IcvCekDokumen::create($cekDokumenDataToInsert);

            $this->command->info("Created IcvCekDokumen ID: {$cekDokumen->id} - Status: {$cekDokumen->status} (ICV ID: {$icv->id})");
        }

        $this->command->info('IcvCekDokumenSeeder completed successfully!');
    }

    /**
     * Parse datetime string to proper format
     */
    private function parseDateTime($datetimeString)
    {
        if (!$datetimeString || $datetimeString === '0000-00-00 00:00:00') {
            return now();
        }

        try {
            return Carbon::parse($datetimeString);
        } catch (\Exception $e) {
            $this->command->warn("Invalid datetime format: {$datetimeString}");
            return now();
        }
    }

    /**
     * Map hasil_verif string to numeric status
     * 1 = Berkas Lengkap
     * 2 = Berkas Tidak Lengkap or anything else
     */
    private function mapStatus($hasilVerif)
    {
        $hasilVerif = trim($hasilVerif);

        if ($hasilVerif === 'Berkas Lengkap') {
            return 1;
        }

        return 2;
    }

    /**
     * Map hasil_verif to numeric value for database
     */
    private function mapHasilVerif($hasilVerif)
    {
        $hasilVerif = trim($hasilVerif);

        if ($hasilVerif === 'Berkas Lengkap') {
            return 1;
        }

        return 2;
    }
}
