<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Izin;
use App\Models\User;
use Carbon\Carbon;

class IzinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_izin.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $izinData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $izinData = $item['data'];
                break;
            }
        }

        if (!$izinData) {
            $this->command->error('No izin data found in JSON file');
            return;
        }

        $this->command->info('Processing ' . count($izinData) . ' izin records...');

        foreach ($izinData as $record) {
            // Skip if ID already exists
            if (!empty($record['id']) && Izin::where('id', $record['id'])->exists()) {
                $this->command->warn("Izin with ID {$record['id']} already exists. Skipping...");
                continue;
            }

            // Skip if id_klinik is 0 or empty
            if (empty($record['id_klinik']) || $record['id_klinik'] == '0') {
                $this->command->warn("Skipping izin ID {$record['id']} - id_klinik is 0 or empty");
                continue;
            }

            // Find user by id_klinik
            $user = User::find($record['id_klinik']);
            if (!$user) {
                $this->command->warn("User with ID {$record['id_klinik']} not found for izin ID {$record['id']}. Skipping...");
                continue;
            }

            // Prepare izin data
            $izinDataToInsert = [
                'id' => $record['id'] ?? null,
                'user_id' => $user->id,
                'kategori_izin' => $record['kategori_izin'] ?? '',
                'tanggal_pengajuan' => $this->parseDate($record['tanggal_pengajuan'] ?? null),
                'nomor_surat' => $record['nomor_surat'] ?? '',
                'tanggal_surat' => $this->parseDate($record['tanggal_surat'] ?? null),
                'file1' => $this->cleanFileName($record['file_01'] ?? ''),
                'file2' => $this->cleanFileName($record['file_02'] ?? ''),
                'file3' => $this->cleanFileName($record['file_03'] ?? ''),
                'file4' => $this->cleanFileName($record['file_04'] ?? ''),
                'file5' => $this->cleanFileName($record['file_05'] ?? ''),
                'file6' => $this->cleanFileName($record['file_06'] ?? ''),
                'file7' => $this->cleanFileName($record['file_07'] ?? ''),
                'file8' => $this->cleanFileName($record['file_08'] ?? ''),
                'file9' => $this->cleanFileName($record['file_09'] ?? ''),
                'file10' => $this->cleanFileName($record['file_10'] ?? ''),
                'file11' => $this->cleanFileName($record['file_11'] ?? ''),
                'file12' => $this->cleanFileName($record['file_12'] ?? ''),
                'file13' => $this->cleanFileName($record['file_13'] ?? ''),
                'file14' => $this->cleanFileName($record['file_14'] ?? ''),
                'status' => $this->mapStatus($record['status'] ?? '1'),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create izin record
            $izin = Izin::create($izinDataToInsert);

            $this->command->info("Created izin ID: {$izin->id} - {$izin->kategori_izin} (User: {$user->name})");
        }

        $this->command->info('IzinSeeder completed successfully!');
    }

    /**
     * Parse date string to proper format
     */
    private function parseDate($dateString)
    {
        if (!$dateString || $dateString === '0000-00-00' || $dateString === '0') {
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
     * Clean filename - remove empty strings and incomplete extensions
     */
    /**
     * Clean filename - remove empty strings and incomplete extensions
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

        // Add izin-documents/ prefix to valid filenames
        return 'izin-documents/' . $filename;
    }

    /**
     * Map status values
     */
    private function mapStatus($status)
    {
        // Keep status as is for now, could be modified based on requirements
        return (int)$status;
    }
}
