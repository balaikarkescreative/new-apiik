<?php

namespace Database\Seeders;

use App\Models\Icv;
use App\Models\IcvAlokasi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IcvAlokasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_icv_alokasi.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $alokasiData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $alokasiData = $item['data'];
                break;
            }
        }

        if (! $alokasiData) {
            $this->command->error('No ICV alokasi data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($alokasiData).' ICV alokasi records...');

        foreach ($alokasiData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && IcvAlokasi::where('id', $record['id'])->exists()) {
                $this->command->warn("IcvAlokasi with ID {$record['id']} already exists. Skipping...");

                continue;
            }

            // Skip if id_icv is 0 or empty
            if (empty($record['id_icv']) || $record['id_icv'] == '0') {
                $this->command->warn("Skipping ICV alokasi ID {$record['id']} - id_icv is 0 or empty");

                continue;
            }

            // Find ICV by id_icv
            $icv = Icv::find($record['id_icv']);
            if (! $icv) {
                $this->command->warn("ICV with ID {$record['id_icv']} not found for alokasi ID {$record['id']}. Skipping...");

                continue;
            }

            // Prepare alokasi data
            $alokasiDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_icv' => $icv->id,
                'icv_disetujui' => (int) ($record['icv_disetujui'] ?? 0),
                'status' => $this->mapStatus($record['status'] ?? '1'),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IcvAlokasi record
            $alokasi = IcvAlokasi::create($alokasiDataToInsert);

            $this->command->info("Created IcvAlokasi ID: {$alokasi->id} - {$alokasi->icv_disetujui} units approved (ICV ID: {$icv->id})");
        }

        $this->command->info('IcvAlokasiSeeder completed successfully!');
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
