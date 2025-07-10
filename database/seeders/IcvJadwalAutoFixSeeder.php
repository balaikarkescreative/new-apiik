<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Icv;
use App\Models\IcvBuktiBayar;
use App\Models\IcvSbbk;
use App\Models\IcvJadwal;
use Illuminate\Support\Facades\DB;

class IcvJadwalAutoFixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua ICV yang sudah punya file_bukti_bayar
        $icvIds = IcvBuktiBayar::whereNotNull('file_bukti_bayar')
            ->where('file_bukti_bayar', '!=', '')
            ->pluck('id_icv');

        $this->command->info('Memproses ' . $icvIds->count() . ' ICV dengan file_bukti_bayar...');

        foreach ($icvIds as $id_icv) {
            $icv = Icv::find($id_icv);
            if (!$icv) continue;

            $buktiBayar = IcvBuktiBayar::where('id_icv', $id_icv)
                ->whereNotNull('file_bukti_bayar')
                ->where('file_bukti_bayar', '!=', '')
                ->orderByDesc('date_updated')
                ->first();

            // Cek apakah sudah ada jadwal
            $jadwal = IcvJadwal::where('id_icv', $id_icv)->first();

            // Cek apakah sudah ada SBBK
            $sbbk = IcvSbbk::where('id_icv', $id_icv)->first();

            // Jika belum ada jadwal atau jadwal_pengambilan null, buat/isi
            if (!$jadwal || !$jadwal->jadwal_pengambilan) {
                $jadwalDate = $buktiBayar ? $buktiBayar->date_updated->toDateString() : now()->toDateString();
                IcvJadwal::updateOrCreate(
                    ['id_icv' => $id_icv],
                    [
                        'jadwal_pengambilan' => $jadwalDate,
                        'status' => 1,
                    ]
                );
                $this->command->info("Jadwal pengambilan ICV $id_icv diisi: $jadwalDate");
            }

            // Jika belum ada SBBK atau sinkarkes null/belum, update/isi
            if (!$sbbk) {
                IcvSbbk::create([
                    'id_icv' => $id_icv,
                    // 'file_sbbk' => null,
                    'sinkarkes' => 'Sudah',
                    'date_updated' => now(),
                ]);
                $this->command->info("SBBK ICV $id_icv dibuat dengan sinkarkes: Sudah");
            } elseif (empty($sbbk->sinkarkes) || strtolower($sbbk->sinkarkes) == 'belum' || is_null($sbbk->sinkarkes)) {
                $sbbk->sinkarkes = 'Sudah';
                $sbbk->date_updated = now();
                $sbbk->save();
                $this->command->info("SBBK ICV $id_icv diupdate menjadi sinkarkes: Sudah");
            }
        }

        $this->command->info('Selesai autofix jadwal & sinkarkes untuk ICV!');
    }
}
