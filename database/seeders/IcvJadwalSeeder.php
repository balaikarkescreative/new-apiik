<?php

namespace Database\Seeders;

use App\Models\Icv;
use App\Models\IcvJadwal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IcvJadwalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_icv_jadwal.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $jadwalData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $jadwalData = $item['data'];
                break;
            }
        }

        if (! $jadwalData) {
            $this->command->error('No ICV jadwal data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($jadwalData).' ICV jadwal records...');

        foreach ($jadwalData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && IcvJadwal::where('id', $record['id'])->exists()) {
                $this->command->warn("IcvJadwal with ID {$record['id']} already exists. Skipping...");

                continue;
            }

            // Skip if id_icv is 0 or empty
            if (empty($record['id_icv']) || $record['id_icv'] == '0') {
                $this->command->warn("Skipping ICV jadwal ID {$record['id']} - id_icv is 0 or empty");

                continue;
            }

            // Find ICV by id_icv
            $icv = Icv::find($record['id_icv']);
            if (! $icv) {
                $this->command->warn("ICV with ID {$record['id_icv']} not found for jadwal ID {$record['id']}. Skipping...");

                continue;
            }

            // Prepare jadwal data
            $jadwalDataToInsert = [
                'id_icv' => $icv->id,
                'jadwal_pengambilan' => $this->parseDate($record['jadwal_pengambilan'] ?? null),
                'status' => $this->mapStatus($record['status'] ?? '1'),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IcvJadwal record
            $jadwal = IcvJadwal::create($jadwalDataToInsert);

            $this->command->info("Created IcvJadwal ID: {$jadwal->id} - {$jadwal->jadwal_pengambilan} (ICV ID: {$icv->id})");
        }

        $this->command->info('IcvJadwalSeeder completed successfully!');
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

    /**
     * Map status values - change 1 to 0
     */
    private function mapStatus($status)
    {
        $status = (int) $status;

        if ($status === 1) {
            return 0;
        }

        return $status;
    }
}
