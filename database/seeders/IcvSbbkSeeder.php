<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IcvSbbk;
use App\Models\Icv;
use Carbon\Carbon;

class IcvSbbkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_icv_sbbk.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $sbbkData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $sbbkData = $item['data'];
                break;
            }
        }

        if (!$sbbkData) {
            $this->command->error('No ICV SBBK data found in JSON file');
            return;
        }

        $this->command->info('Processing ' . count($sbbkData) . ' ICV SBBK records...');

        foreach ($sbbkData as $record) {
            // Skip if ID already exists
            if (!empty($record['id']) && IcvSbbk::where('id', $record['id'])->exists()) {
                $this->command->warn("IcvSbbk with ID {$record['id']} already exists. Skipping...");
                continue;
            }

            // Skip if id_icv is 0 or empty
            if (empty($record['id_icv']) || $record['id_icv'] == '0') {
                $this->command->warn("Skipping ICV SBBK ID {$record['id']} - id_icv is 0 or empty");
                continue;
            }

            // Find ICV by id_icv
            $icv = Icv::find($record['id_icv']);
            if (!$icv) {
                $this->command->warn("ICV with ID {$record['id_icv']} not found for SBBK ID {$record['id']}. Skipping...");
                continue;
            }

            // Prepare SBBK data
            $sbbkDataToInsert = [
                'id_icv' => $icv->id,
                'file_sbbk' => $this->cleanFileName($record['file_sbbk'] ?? ''),
                'sinkarkes' => $this->mapSinkarkes($record['sinkarkes'] ?? ''),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IcvSbbk record
            $sbbk = IcvSbbk::create($sbbkDataToInsert);

            $this->command->info("Created IcvSbbk ID: {$sbbk->id} - {$sbbk->sinkarkes} (ICV ID: {$icv->id})");
        }

        $this->command->info('IcvSbbkSeeder completed successfully!');
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
     * Clean filename - remove empty strings and add prefix
     */
    private function cleanFileName($filename)
    {
        $filename = trim($filename);

        // Return null for empty strings or just quotes
        if (empty($filename) || $filename === '""' || $filename === "''") {
            return null;
        }

        // Remove surrounding quotes if present
        $filename = trim($filename, '"\'');

        // Check for incomplete filenames (ending with just a period)
        if (substr($filename, -1) === '.') {
            return null;
        }

        // Return null if filename is empty after cleaning
        if (empty($filename)) {
            return null;
        }

        // Add icv-sbbk/ prefix to valid filenames
        return 'icv/sbbk/' . $filename;
    }

    /**
     * Map sinkarkes values
     */
    private function mapSinkarkes($sinkarkes)
    {
        $sinkarkes = trim($sinkarkes);

        // Map old values to new ones if needed
        if ($sinkarkes === 'Sudah') {
            return 'Sudah';
        }

        if ($sinkarkes === 'Belum') {
            return 'Belum';
        }

        // Default value if empty
        if (empty($sinkarkes)) {
            return 'Belum';
        }

        return $sinkarkes;
    }
}
