<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vaksinator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class VaksinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFile = database_path('seeders/tbl_akun_vaksinator.json');

        if (! file_exists($jsonFile)) {
            $this->command->error("File {$jsonFile} not found!");

            return;
        }

        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        if (! $data || ! isset($data[2]['data'])) {
            $this->command->error('Invalid JSON structure!');

            return;
        }

        $records = $data[2]['data'];
        $created = 0;
        $skipped = 0;

        $this->command->info('Starting to seed vaksinator data...');

        foreach ($records as $record) {
            try {
                // Find user by id_akun
                $user = User::find($record['id_akun']);
                if (! $user) {
                    $this->command->warn("User with ID {$record['id_akun']} not found for vaksinator ID {$record['id']}. Skipping...");
                    $skipped++;

                    continue;
                }

                // Parse dates
                $masaAktifIzin = $this->parseDate($record['masa_aktif_izin_praktek']);
                $masaAktifSertifikat = $this->parseDate($record['masa_aktif_sertifikat']);
                $dateUpdated = $this->parseDateTime($record['date_updated']);

                // Create or update vaksinator with explicit ID
                $vaksinator = Vaksinator::updateOrCreate(
                    ['id' => $record['id']],
                    [
                        'id' => $record['id'], // Explicitly set the ID
                        'id_akun' => $user->id,
                        'nama_vaksinator' => $record['nama_vaksinator'],
                        'nomor_izin_praktek' => $record['nomor_izin_praktek'] === '-' ? null : $record['nomor_izin_praktek'],
                        'masa_aktif_izin_praktek' => $masaAktifIzin,
                        'nomor_sertifikat_vaksinator' => $record['nomor_sertifikat_vaksinator'] === '-' ? null : $record['nomor_sertifikat_vaksinator'],
                        'masa_aktif_sertifikat' => $masaAktifSertifikat,
                        'date_updated' => $dateUpdated,
                    ]
                );

                $this->command->info("Processed vaksinator ID {$record['id']} - {$record['nama_vaksinator']}");
                $created++;

            } catch (\Exception $e) {
                $this->command->error("Error processing vaksinator ID {$record['id']}: ".$e->getMessage());
                $skipped++;

                continue;
            }
        }

        $this->command->info('Vaksinator seeding completed!');
        $this->command->info("Created/Updated: {$created}");
        $this->command->info("Skipped: {$skipped}");
    }

    /**
     * Parse date string to Carbon instance
     */
    private function parseDate($dateString)
    {
        if (empty($dateString) || $dateString === '-' || $dateString === '0000-00-00') {
            return null;
        }

        try {
            // Handle various date formats
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                return Carbon::createFromFormat('Y-m-d', $dateString);
            }

            // Try parsing as standard date
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$dateString}");

            return null;
        }
    }

    /**
     * Parse datetime string to Carbon instance
     */
    private function parseDateTime($dateTimeString)
    {
        if (empty($dateTimeString) || $dateTimeString === '-') {
            return now();
        }

        try {
            return Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            Log::warning("Failed to parse datetime: {$dateTimeString}");

            return now();
        }
    }
}
