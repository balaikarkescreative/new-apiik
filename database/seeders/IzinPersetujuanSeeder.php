<?php

namespace Database\Seeders;

use App\Models\Izin;
use App\Models\IzinPersetujuan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IzinPersetujuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_izin_persetujuan.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $izinPersetujuanData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $izinPersetujuanData = $item['data'];
                break;
            }
        }

        if (! $izinPersetujuanData) {
            $this->command->error('No izin persetujuan data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($izinPersetujuanData).' izin persetujuan records...');

        $processed = 0;
        $skipped = 0;

        foreach ($izinPersetujuanData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && IzinPersetujuan::where('id', $record['id'])->exists()) {
                $this->command->warn("IzinPersetujuan with ID {$record['id']} already exists. Skipping...");
                $skipped++;

                continue;
            }

            // Skip if id_izin is 0 or empty
            if (empty($record['id_izin']) || $record['id_izin'] == '0') {
                $this->command->warn("Skipping izin persetujuan ID {$record['id']} - id_izin is 0 or empty");
                $skipped++;

                continue;
            }

            // Find Izin by id_izin
            $izin = Izin::find($record['id_izin']);
            if (! $izin) {
                $this->command->warn("Izin with ID {$record['id_izin']} not found for izin persetujuan ID {$record['id']}. Skipping...");
                $skipped++;

                continue;
            }

            // Prepare izin persetujuan data
            $izinPersetujuanDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_izin' => $izin->id,
                'file_persetujuan' => $this->cleanFileName($record['file_persetujuan'] ?? ''),
                'status' => $this->mapStatus($record['status'] ?? '1'),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IzinPersetujuan record
            $izinPersetujuan = IzinPersetujuan::create($izinPersetujuanDataToInsert);

            $statusLabel = $this->getStatusLabel($izinPersetujuan->status);
            $this->command->info("Created IzinPersetujuan ID: {$izinPersetujuan->id} - {$statusLabel} (Izin ID: {$izin->id})");
            $processed++;
        }

        $this->command->info('IzinPersetujuanSeeder completed successfully!');
        $this->command->info("Processed: {$processed} records");
        $this->command->info("Skipped: {$skipped} records");
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
     * Clean filename - remove empty strings and add prefix
     */
    private function cleanFileName($filename)
    {
        // Handle null values
        if ($filename === null) {
            return null;
        }

        $filename = trim($filename);

        // Return null for empty strings or just quotes
        if (empty($filename) || $filename === '""' || $filename === "''") {
            return null;
        }

        // Remove surrounding quotes if present
        $filename = trim($filename, '"\'');

        // Check for incomplete filenames (ending with just a period or incomplete extension)
        if (substr($filename, -1) === '.' || substr($filename, -1) === '') {
            return null;
        }

        // Return null if filename is empty after cleaning
        if (empty($filename)) {
            return null;
        }

        // Add izin-persetujuan-documents/ prefix to valid filenames
        return 'surat-persetujuan/'.$filename;
    }

    /**
     * Map status values - change 1 to 0 based on your previous pattern
     */
    private function mapStatus($status)
    {
        $status = (int) $status;

        // Based on your previous seeders pattern, change 1 to 0
        if ($status === 1) {
            return 0;
        }

        return $status;
    }

    /**
     * Get status label for display
     */
    private function getStatusLabel($status)
    {
        return match ($status) {
            0 => 'Disetujui',
            1 => 'Belum Disetujui',
            default => 'Belum Disetujui',
        };
    }
}
