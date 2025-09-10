<?php

namespace Database\Seeders;

use App\Models\Icv;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IcvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_icv.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $icvData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $icvData = $item['data'];
                break;
            }
        }

        if (! $icvData) {
            $this->command->error('No ICV data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($icvData).' ICV records...');

        foreach ($icvData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && Icv::where('id', $record['id'])->exists()) {
                $this->command->warn("ICV with ID {$record['id']} already exists. Skipping...");

                continue;
            }

            // Skip if id_klinik is 0 or empty
            if (empty($record['id_klinik']) || $record['id_klinik'] == '0') {
                $this->command->warn("Skipping ICV ID {$record['id']} - id_klinik is 0 or empty");

                continue;
            }

            // Find user by id_klinik
            $user = User::find($record['id_klinik']);
            if (! $user) {
                $this->command->warn("User with ID {$record['id_klinik']} not found for ICV ID {$record['id']}. Skipping...");

                continue;
            }

            // Prepare ICV data
            $icvDataToInsert = [
                'id' => $record['id'] ?? null,
                'user_id' => $user->id,
                'tanggal_pengajuan' => $this->parseDate($record['tanggal_pengajuan'] ?? null),
                'jumlah_icv' => (int) ($record['jumlah_icv'] ?? 0),
                'file1' => $this->cleanFileName($record['file_1'] ?? ''),
                'file2' => $this->cleanFileName($record['file_2'] ?? ''),
                'file3' => $this->cleanFileName($record['file_3'] ?? ''),
                'file4' => $this->cleanFileName($record['file_4'] ?? ''),
                'file5' => $this->cleanFileName($record['file_5'] ?? ''),
                'file6' => $this->cleanFileName($record['file_6'] ?? ''),
                'file7' => $this->cleanFileName($record['file_7'] ?? ''),
                'status' => $this->mapStatus($record['status'] ?? 0),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create ICV record
            $icv = Icv::create($icvDataToInsert);

            $this->command->info("Created ICV ID: {$icv->id} - {$icv->jumlah_icv} units (User: {$user->name})");
        }

        $this->command->info('IcvSeeder completed successfully!');
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

        // Add icv-documents/ prefix to valid filenames
        return 'icv-documents/'.$filename;
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
