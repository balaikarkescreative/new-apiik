<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrasi ini mengisi data IzinVisitasi untuk data lama yang sudah memiliki izinPersetujuan.
     * Data lama dianggap sudah "Memenuhi syarat" karena sudah sampai tahap persetujuan.
     */
    public function up(): void
    {
        // Ambil semua data izin yang sudah punya persetujuan tapi belum punya hasil visitasi
        $izinsWithPersetujuan = DB::table('tbl_izin')
            ->join('tbl_izin_persetujuan', 'tbl_izin.id', '=', 'tbl_izin_persetujuan.id_izin')
            ->leftJoin('tbl_izin_visitasi', 'tbl_izin.id', '=', 'tbl_izin_visitasi.id_izin')
            ->leftJoin('tbl_izin_dokumen_pks', 'tbl_izin.id', '=', 'tbl_izin_dokumen_pks.id_izin')
            ->whereNull('tbl_izin_visitasi.id') // Belum ada hasil visitasi
            ->whereNotNull('tbl_izin_persetujuan.file_persetujuan') // Sudah ada file persetujuan
            ->select(
                'tbl_izin.id as id_izin',
                'tbl_izin_dokumen_pks.tanggal_visitasi',
                'tbl_izin_persetujuan.date_updated as tanggal_persetujuan'
            )
            ->get();

        // Insert data visitasi dengan status "Memenuhi syarat" untuk data lama
        foreach ($izinsWithPersetujuan as $izin) {
            DB::table('tbl_izin_visitasi')->insert([
                'id_izin' => $izin->id_izin,
                'tanggal_visitasi' => $izin->tanggal_visitasi ?? $izin->tanggal_persetujuan,
                'status' => 'Memenuhi syarat',
                'catatan' => null,
                'date_updated' => $izin->tanggal_persetujuan,
            ]);
        }

        // Log hasil migrasi
        $count = $izinsWithPersetujuan->count();
        echo "✓ Berhasil migrasi {$count} data izin lama ke IzinVisitasi dengan status 'Memenuhi syarat'\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus data visitasi yang dibuat oleh migrasi ini
        // (data dengan status "Memenuhi syarat" dan catatan null yang berkaitan dengan persetujuan)
        DB::table('tbl_izin_visitasi')
            ->where('status', 'Memenuhi syarat')
            ->whereNull('catatan')
            ->whereIn('id_izin', function($query) {
                $query->select('id_izin')
                    ->from('tbl_izin_persetujuan')
                    ->whereNotNull('file_persetujuan');
            })
            ->delete();

        echo "✓ Rollback: Data visitasi hasil migrasi telah dihapus\n";
    }
};
