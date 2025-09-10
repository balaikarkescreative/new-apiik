<?php

namespace Database\Seeders;

use App\Models\Izin;
use App\Models\IzinDokumenPks;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IzinDokumenPksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_izin_dokumen_pks.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $izinDokumenPksData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $izinDokumenPksData = $item['data'];
                break;
            }
        }

        if (! $izinDokumenPksData) {
            $this->command->error('No izin dokumen PKS data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($izinDokumenPksData).' izin dokumen PKS records...');

        foreach ($izinDokumenPksData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && IzinDokumenPks::where('id', $record['id'])->exists()) {
                $this->command->warn("IzinDokumenPks with ID {$record['id']} already exists. Skipping...");

                continue;
            }

            // Skip if id_izin is 0 or empty
            if (empty($record['id_izin']) || $record['id_izin'] == '0') {
                $this->command->warn("Skipping izin dokumen PKS ID {$record['id']} - id_izin is 0 or empty");

                continue;
            }

            // Find Izin by id_izin
            $izin = Izin::find($record['id_izin']);
            if (! $izin) {
                $this->command->warn("Izin with ID {$record['id_izin']} not found for izin dokumen PKS ID {$record['id']}. Skipping...");

                continue;
            }

            // Prepare izin dokumen PKS data
            $izinDokumenPksDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_izin' => $izin->id,
                'dokumen_pks_klinik' => $this->cleanFileName($record['dokumen_pks_klinik'] ?? ''),
                'dokumen_pks_bkk' => $this->cleanFileName($record['dokumen_pks_bkk'] ?? ''),
                'status_dokumen' => $this->mapStatusDokumen($record['status_dokumen'] ?? ''),
                'tanggal_visitasi' => $this->parseDate($record['tanggal_visitasi'] ?? null),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IzinDokumenPks record
            $izinDokumenPks = IzinDokumenPks::create($izinDokumenPksDataToInsert);

            $statusLabel = $this->getStatusLabel($izinDokumenPks->status_dokumen);
            $this->command->info("Created IzinDokumenPks ID: {$izinDokumenPks->id} - {$statusLabel} (Izin ID: {$izin->id})");
        }

        $this->command->info('IzinDokumenPksSeeder completed successfully!');
    }

    /**
     * Parse date string to proper format
     */
    private function parseDate($dateString)
    {
        if (! $dateString || $dateString === '0000-00-00' || $dateString === '0' || $dateString === null) {
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

        // Check for incomplete filenames (ending with just a period)
        if (substr($filename, -1) === '.') {
            return null;
        }

        // Return null if filename is empty after cleaning
        if (empty($filename)) {
            return null;
        }

        // Add izin-pks-documents/ prefix to valid filenames
        return 'perjanjian-kerjasama/'.$filename;
    }

    /**
     * Map status_dokumen values
     */
    private function mapStatusDokumen($statusDokumen)
    {
        $statusDokumen = trim($statusDokumen);

        // Keep original values
        if ($statusDokumen === 'sign both') {
            return 'sign both';
        }

        if ($statusDokumen === 'not sign') {
            return 'not sign';
        }

        // Default value if empty
        if (empty($statusDokumen)) {
            return 'not sign';
        }

        return $statusDokumen;
    }

    /**
     * Get status label for display
     */
    private function getStatusLabel($status)
    {
        return match ($status) {
            'sign both' => 'Ditandatangani Kedua Pihak',
            'not sign' => 'Belum Ditandatangani',
            default => $status,
        };
    }
}
