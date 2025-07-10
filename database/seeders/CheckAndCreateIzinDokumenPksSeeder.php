<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Izin;
use App\Models\IzinPersetujuan;
use App\Models\IzinDokumenPks;
use Carbon\Carbon;

class CheckAndCreateIzinDokumenPksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua izin yang memiliki file persetujuan
        $izinWithPersetujuan = IzinPersetujuan::with('izin')
            ->whereNotNull('file_persetujuan')
            ->where('file_persetujuan', '!=', '')
            ->get();

        $this->command->info('Found ' . $izinWithPersetujuan->count() . ' izin records with persetujuan files');

        $createdCount = 0;
        $existingCount = 0;
        $skippedCount = 0;

        foreach ($izinWithPersetujuan as $persetujuan) {
            // Skip jika izin tidak ditemukan
            if (!$persetujuan->izin) {
                $this->command->warn("Izin not found for persetujuan ID: {$persetujuan->id}");
                $skippedCount++;
                continue;
            }

            $izin = $persetujuan->izin;

            // Cek apakah sudah ada record IzinDokumenPks untuk izin ini
            $existingDokumenPks = IzinDokumenPks::where('id_izin', $izin->id)->first();

            if ($existingDokumenPks) {
                $this->command->info("IzinDokumenPks already exists for Izin ID: {$izin->id}");
                $existingCount++;
                continue;
            }

            // Buat record IzinDokumenPks baru
            try {
                $tanggalVisitasi = $this->calculateTanggalVisitasi($persetujuan->date_updated);
                $dateUpdated = $persetujuan->date_updated ? $persetujuan->date_updated : now();

                $dokumenPksData = [
                    'id_izin' => $izin->id,
                    'dokumen_pks_klinik' => null,
                    'dokumen_pks_bkk' => null,
                    'status_dokumen' => 'not sign',
                    'tanggal_visitasi' => $tanggalVisitasi,
                    'date_updated' => $dateUpdated,
                ];

                $izinDokumenPks = IzinDokumenPks::create($dokumenPksData);

                $klinikName = $izin->klinik ? $izin->klinik->nama : 'Unknown';
                $this->command->info("✓ Created IzinDokumenPks for Izin ID: {$izin->id} (Klinik: {$klinikName})");
                $createdCount++;

            } catch (\Exception $e) {
                $this->command->error("Failed to create IzinDokumenPks for Izin ID: {$izin->id}. Error: " . $e->getMessage());
                $skippedCount++;
            }
        }

        // Summary
        $this->command->info('');
        $this->command->info('=== SUMMARY ===');
        $this->command->info("Total izin with persetujuan: " . $izinWithPersetujuan->count());
        $this->command->info("Created new records: {$createdCount}");
        $this->command->info("Already existing: {$existingCount}");
        $this->command->info("Skipped/Failed: {$skippedCount}");
        $this->command->info('CheckAndCreateIzinDokumenPksSeeder completed successfully!');
    }

    /**
     * Calculate tanggal_visitasi based on date_updated
     * Menggunakan tanggal dari date_updated persetujuan
     */
    private function calculateTanggalVisitasi($dateUpdated)
    {
        if (!$dateUpdated) {
            return now()->format('Y-m-d');
        }

        try {
            return Carbon::parse($dateUpdated)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->command->warn("Invalid date format for date_updated: {$dateUpdated}");
            return now()->format('Y-m-d');
        }
    }
}
