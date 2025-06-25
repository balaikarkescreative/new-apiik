<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IcvBuktiBayar;
use App\Models\Icv;

class StatusBuktiBayarSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting ICV Bukti Bayar status analysis...');

        // First, analyze current status distribution
        $statusDistribution = IcvBuktiBayar::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $this->command->info('Current status distribution:');
        foreach ($statusDistribution as $dist) {
            $this->command->info("Status {$dist->status}: {$dist->count} records");
        }

        $this->command->info('');
        $this->command->info('=== ANALISIS JADWAL PENGAMBILAN ===');

        // Analisis mendalam untuk jadwal pengambilan
        $this->analyzeJadwalPengambilan();

        $this->command->info('');
        $this->command->info('Status mapping:');
        $this->command->info('Status 1 = Bukti bayar tidak valid (belum ada jadwal distribusi)');
        $this->command->info('Status 2 = Bukti bayar valid (sudah ada jadwal distribusi)');
        $this->command->info('');

        // Check if we should proceed
        if (!$this->command->confirm('Do you want to proceed with status updates?')) {
            $this->command->info('Operation cancelled.');
            return;
        }

        // Proceed with updates
        $icvs = Icv::with(['icvBuktiBayar', 'icvJadwal'])
            ->whereHas('icvBuktiBayar')
            ->get();

        $updated = 0;
        $skipped = 0;
        $validCount = 0;
        $invalidCount = 0;

        foreach ($icvs as $icv) {
            $newStatus = $this->determineStatus($icv);
            $currentStatus = $icv->icvBuktiBayar->status;

            // Count for statistics
            if ($newStatus == 2) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            // Only update if there's actually a change
            if ($currentStatus !== $newStatus) {
                $icv->icvBuktiBayar->update([
                    'status' => $newStatus,
                    'date_updated' => now(),
                ]);

                $updated++;
                $statusText = $newStatus == 2 ? 'Valid (ada jadwal distribusi)' : 'Tidak Valid (belum ada jadwal distribusi)';
                $this->command->info("✅ Updated ICV ID: {$icv->id} - Status: {$currentStatus} → {$newStatus} ({$statusText})");
            } else {
                $skipped++;
                $statusText = $newStatus == 2 ? 'Valid (ada jadwal distribusi)' : 'Tidak Valid (belum ada jadwal distribusi)';
                $this->command->info("⏭️  Skipped ICV ID: {$icv->id} - Status unchanged: {$currentStatus} ({$statusText})");
            }
        }

        $this->command->info('');
        $this->command->info("=== Update Summary ===");
        $this->command->info("Total records processed: " . $icvs->count());
        $this->command->info("Updated: {$updated} records");
        $this->command->info("Skipped: {$skipped} records");
        $this->command->info('');
        $this->command->info("=== Status Summary ===");
        $this->command->info("Valid bukti bayar (Status 2): {$validCount} records");
        $this->command->info("Invalid bukti bayar (Status 1): {$invalidCount} records");
    }

    /**
     * Analisis mendalam untuk jadwal pengambilan
     */
    private function analyzeJadwalPengambilan(): void
    {
        // Total ICV dengan bukti bayar
        $totalIcvWithBuktiBayar = Icv::whereHas('icvBuktiBayar')->count();
        $this->command->info("Total ICV dengan bukti bayar: {$totalIcvWithBuktiBayar}");

        // ICV yang memiliki jadwal
        $icvWithJadwal = Icv::whereHas('icvBuktiBayar')
            ->whereHas('icvJadwal', function($query) {
                $query->whereNotNull('jadwal_pengambilan');
            })
            ->count();
        $this->command->info("ICV dengan jadwal pengambilan: {$icvWithJadwal}");

        // ICV yang tidak memiliki jadwal
        $icvWithoutJadwal = $totalIcvWithBuktiBayar - $icvWithJadwal;
        $this->command->info("ICV tanpa jadwal pengambilan: {$icvWithoutJadwal}");

        // ICV yang tidak memiliki record icvJadwal sama sekali
        $icvWithoutJadwalRecord = Icv::whereHas('icvBuktiBayar')
            ->whereDoesntHave('icvJadwal')
            ->count();
        $this->command->info("ICV tanpa record icvJadwal: {$icvWithoutJadwalRecord}");

        // ICV yang memiliki record icvJadwal tapi jadwal_pengambilan NULL
        $icvWithNullJadwal = Icv::whereHas('icvBuktiBayar')
            ->whereHas('icvJadwal', function($query) {
                $query->whereNull('jadwal_pengambilan');
            })
            ->count();
        $this->command->info("ICV dengan record icvJadwal tapi jadwal_pengambilan NULL: {$icvWithNullJadwal}");

        // Persentase
        $percentage = $totalIcvWithBuktiBayar > 0 ?
            round(($icvWithJadwal / $totalIcvWithBuktiBayar) * 100, 2) : 0;
        $this->command->info("Persentase ICV dengan jadwal: {$percentage}%");

        $this->command->info('');

        // Tampilkan detail ICV tanpa jadwal (maksimal 10)
        if ($icvWithoutJadwal > 0) {
            $this->command->info("=== DETAIL ICV TANPA JADWAL PENGAMBILAN (Max 10) ===");

            $icvsWithoutJadwal = Icv::with(['user', 'icvBuktiBayar', 'icvJadwal'])
                ->whereHas('icvBuktiBayar')
                ->where(function($query) {
                    $query->whereDoesntHave('icvJadwal')
                          ->orWhereHas('icvJadwal', function($subquery) {
                              $subquery->whereNull('jadwal_pengambilan');
                          });
                })
                ->limit(10)
                ->get();

            foreach ($icvsWithoutJadwal as $icv) {
                $hasJadwalRecord = $icv->icvJadwal ? 'Ada record' : 'Tidak ada record';
                $jadwalValue = $icv->icvJadwal?->jadwal_pengambilan ?? 'NULL';
                $this->command->info("ICV ID: {$icv->id} | User: {$icv->user->name} | Jadwal Record: {$hasJadwalRecord} | Jadwal: {$jadwalValue}");
            }

            if ($icvWithoutJadwal > 10) {
                $remaining = $icvWithoutJadwal - 10;
                $this->command->info("... dan {$remaining} ICV lainnya tanpa jadwal pengambilan");
            }
        }

        $this->command->info('');

        // Kesimpulan
        if ($icvWithJadwal == $totalIcvWithBuktiBayar) {
            $this->command->info("✅ KESIMPULAN: SEMUA bukti bayar memiliki jadwal pengambilan");
        } else {
            $this->command->info("❌ KESIMPULAN: TIDAK SEMUA bukti bayar memiliki jadwal pengambilan");
            $this->command->info("   {$icvWithoutJadwal} dari {$totalIcvWithBuktiBayar} bukti bayar belum memiliki jadwal pengambilan");
        }
    }

    /**
     * Tentukan status berdasarkan jadwal distribusi
     * Status 2 = Valid (ada jadwal distribusi)
     * Status 1 = Tidak Valid (belum ada jadwal distribusi)
     */
    private function determineStatus(Icv $icv): int
    {
        // Cek apakah ada jadwal distribusi
        if ($icv->icvJadwal && $icv->icvJadwal->jadwal_pengambilan) {
            return 2; // Valid - sudah ada jadwal distribusi
        }

        return 1; // Tidak Valid - belum ada jadwal distribusi
    }
}
