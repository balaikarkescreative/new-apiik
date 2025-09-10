<?php

namespace Database\Seeders;

use App\Models\Icv;
use App\Models\IcvBuktiBayar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IcvBuktiBayarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_icv_bukti_bayar.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $buktiBayarData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $buktiBayarData = $item['data'];
                break;
            }
        }

        if (! $buktiBayarData) {
            $this->command->error('No ICV bukti bayar data found in JSON file');

            return;
        }

        $this->command->info('Processing '.count($buktiBayarData).' ICV bukti bayar records...');

        foreach ($buktiBayarData as $record) {
            // Skip if ID already exists
            if (! empty($record['id']) && IcvBuktiBayar::where('id', $record['id'])->exists()) {
                $this->command->warn("IcvBuktiBayar with ID {$record['id']} already exists. Skipping...");

                continue;
            }

            // Skip if id_icv is 0 or empty
            if (empty($record['id_icv']) || $record['id_icv'] == '0') {
                $this->command->warn("Skipping ICV bukti bayar ID {$record['id']} - id_icv is 0 or empty");

                continue;
            }

            // Find ICV by id_icv
            $icv = Icv::find($record['id_icv']);
            if (! $icv) {
                $this->command->warn("ICV with ID {$record['id_icv']} not found for bukti bayar ID {$record['id']}. Skipping...");

                continue;
            }

            // Prepare bukti bayar data
            $buktiBayarDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_icv' => $icv->id,
                'file_bukti_bayar' => $this->cleanFileName($record['file_bukti_bayar'] ?? ''),
                'catatan' => $record['catatan'] ?? '',
                'distribusi_icv' => $this->mapDistribusiIcv($record['distribusi_icv'] ?? ''),
                'status' => $this->mapStatus($record['status'] ?? '1'),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IcvBuktiBayar record
            $buktiBayar = IcvBuktiBayar::create($buktiBayarDataToInsert);

            $this->command->info("Created IcvBuktiBayar ID: {$buktiBayar->id} - {$buktiBayar->distribusi_icv} (ICV ID: {$icv->id})");
        }

        $this->command->info('IcvBuktiBayarSeeder completed successfully!');
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

        // Add icv-bukti-bayar/ prefix to valid filenames
        return 'icv/bukti-bayar/'.$filename;
    }

    /**
     * Map distribusi_icv values
     */
    private function mapDistribusiIcv($distribusi)
    {
        $distribusi = trim($distribusi);

        // Map old values to new ones if needed
        if ($distribusi === 'Diambil') {
            return 'Diambil';
        }

        if ($distribusi === 'Dikirim') {
            return 'Dikirim';
        }

        // Default value if empty
        if (empty($distribusi)) {
            return 'Belum Diambil';
        }

        return $distribusi;
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
