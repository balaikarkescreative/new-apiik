<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IzinDokumen;
use App\Models\Izin;
use Carbon\Carbon;

class IzinDokumenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_izin_dokumen.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $izinDokumenData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $izinDokumenData = $item['data'];
                break;
            }
        }

        if (!$izinDokumenData) {
            $this->command->error('No izin dokumen data found in JSON file');
            return;
        }

        $this->command->info('Processing ' . count($izinDokumenData) . ' izin dokumen records...');

        foreach ($izinDokumenData as $record) {
            // Skip if ID already exists
            if (!empty($record['id']) && IzinDokumen::where('id', $record['id'])->exists()) {
                $this->command->warn("IzinDokumen with ID {$record['id']} already exists. Skipping...");
                continue;
            }

            // Skip if id_izin is 0 or empty
            if (empty($record['id_izin']) || $record['id_izin'] == '0') {
                $this->command->warn("Skipping izin dokumen ID {$record['id']} - id_izin is 0 or empty");
                continue;
            }

            // Find Izin by id_izin
            $izin = Izin::find($record['id_izin']);
            if (!$izin) {
                $this->command->warn("Izin with ID {$record['id_izin']} not found for izin dokumen ID {$record['id']}. Skipping...");
                continue;
            }

            // Prepare izin dokumen data
            $izinDokumenDataToInsert = [
                'id' => $record['id'] ?? null,
                'izin_id' => $izin->id,
                'hasil' => $this->mapHasilVerifIzin($record['hasil_verif_izin'] ?? ''),
                'catatan' => $record['catatan'] ?? '',
                'status' => $this->mapStatus($record['status'] ?? '0'),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IzinDokumen record
            $izinDokumen = IzinDokumen::create($izinDokumenDataToInsert);

            $hasilLabel = $this->getHasilLabel($izinDokumen->hasil);
            $this->command->info("Created IzinDokumen ID: {$izinDokumen->id} - {$hasilLabel} (Izin ID: {$izin->id})");
        }

        $this->command->info('IzinDokumenSeeder completed successfully!');
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
     * Map hasil_verif_izin string to numeric value
     * 1 = Berkas Lengkap
     * 2 = Berkas Tidak Lengkap
     * 0 = Belum Diverifikasi (default)
     */
    private function mapHasilVerifIzin($hasilVerif)
    {
        $hasilVerif = trim($hasilVerif);

        if ($hasilVerif === 'Berkas Lengkap') {
            return 1;
        }

        if ($hasilVerif === 'Berkas Tidak Lengkap') {
            return 2;
        }

        // Empty or other values = Belum Diverifikasi
        return 0;
    }

    /**
     * Map status values
     */
    private function mapStatus($status)
    {
        return (int)$status;
    }

    /**
     * Get hasil label for display
     */
    private function getHasilLabel($hasil)
    {
        return match($hasil) {
            0 => 'Belum Diverifikasi',
            1 => 'Berkas Lengkap',
            2 => 'Berkas Tidak Lengkap',
            default => 'Belum Diverifikasi',
        };
    }
}
