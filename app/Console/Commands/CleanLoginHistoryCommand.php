<?php

namespace App\Console\Commands;

use App\Models\LoginHistory;
use Illuminate\Console\Command;

class CleanLoginHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clean-login-history
                            {--days=90 : Jumlah hari penyimpanan riwayat login sebelum dihapus}
                            {--force : Jalankan pembersihan tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan data riwayat login yang lebih lama dari jumlah hari tertentu';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days <= 0) {
            $this->error('Parameter --days harus lebih besar dari 0.');

            return self::FAILURE;
        }

        $cutoffDate = now()->subDays($days);
        $count = LoginHistory::where('login_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info("Tidak ada data riwayat login yang lebih lama dari {$days} hari ({$cutoffDate->toDateTimeString()}).");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Ditemukan {$count} catatan riwayat login sebelum {$cutoffDate->format('Y-m-d H:i:s')}. Apakah Anda yakin ingin menghapusnya?")) {
            $this->info('Pembersihan riwayat login dibatalkan.');

            return self::SUCCESS;
        }

        $deleted = LoginHistory::where('login_at', '<', $cutoffDate)->delete();

        $this->info("Berhasil menghapus {$deleted} catatan riwayat login.");

        return self::SUCCESS;
    }
}
