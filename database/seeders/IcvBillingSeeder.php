<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IcvBilling;
use App\Models\Icv;
use Carbon\Carbon;

class IcvBillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/tbl_icv_billing.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $billingData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $billingData = $item['data'];
                break;
            }
        }

        if (!$billingData) {
            $this->command->error('No ICV billing data found in JSON file');
            return;
        }

        $this->command->info('Processing ' . count($billingData) . ' ICV billing records...');

        foreach ($billingData as $record) {
            // Skip if ID already exists
            if (!empty($record['id']) && IcvBilling::where('id', $record['id'])->exists()) {
                $this->command->warn("IcvBilling with ID {$record['id']} already exists. Skipping...");
                continue;
            }

            // Skip if id_icv is 0 or empty
            if (empty($record['id_icv']) || $record['id_icv'] == '0') {
                $this->command->warn("Skipping ICV billing ID {$record['id']} - id_icv is 0 or empty");
                continue;
            }

            // Find ICV by id_icv
            $icv = Icv::find($record['id_icv']);
            if (!$icv) {
                $this->command->warn("ICV with ID {$record['id_icv']} not found for billing ID {$record['id']}. Skipping...");
                continue;
            }

            // Prepare billing data
            $billingDataToInsert = [
                'id' => $record['id'] ?? null,
                'id_icv' => $icv->id,
                'kode_billing' => $record['kode_billing'] ?? '',
                'nominal' => $this->convertCurrencyToNumber($record['nominal'] ?? '0'),
                'status' => $this->mapStatus($record['status'] ?? '1'),
                // 'bayar' => $this->mapBayar($record['bayar'] ?? ''),
                'date_updated' => $this->parseDateTime($record['date_updated'] ?? null),
            ];

            // Create IcvBilling record
            $billing = IcvBilling::create($billingDataToInsert);

            $this->command->info("Created IcvBilling ID: {$billing->id} - Rp. " . number_format($billing->nominal) . " (ICV ID: {$icv->id})");
        }

        $this->command->info('IcvBillingSeeder completed successfully!');
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
     * Convert currency string to number
     * Converts "Rp. 1,250,000" to 1250000
     */
    private function convertCurrencyToNumber($currency)
    {
        // Remove "Rp.", "Rp", and spaces first
        $cleanNumber = str_replace(['Rp.', 'Rp', ' '], '', $currency);

        // Remove dots (thousand separators) but keep the actual number
        $cleanNumber = str_replace('.', '', $cleanNumber);

        // Remove commas if any (some formats might use commas)
        $cleanNumber = str_replace(',', '', $cleanNumber);

        // Convert to integer
        return (int) $cleanNumber;
    }

    /**
     * Map status values - change 1 to 0
     */
    private function mapStatus($status)
    {
        $status = (int)$status;

        if ($status === 1) {
            return 0;
        }

        return $status;
    }

    /**
     * Map bayar field to proper format
     */
    private function mapBayar($bayar)
    {
        $bayar = trim($bayar);

        if ($bayar === 'Sudah') {
            return 'Lunas';
        }

        if (empty($bayar)) {
            return 'Belum Lunas';
        }

        return $bayar;
    }
}
