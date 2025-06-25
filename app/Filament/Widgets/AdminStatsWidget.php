<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Widgets/AdminStatsWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Izin;
use App\Models\Icv;
use App\Models\IcvAlokasi;
use App\Models\IcvSbbk;
use App\Models\IzinDokumen;
use Illuminate\Support\Facades\DB;

class AdminStatsWidget extends BaseWidget
{
    // Tarif ICV berdasarkan regulasi (dalam rupiah)
    private const TARIF_ICV = 25000; // Rp 50.000 per ICV

    protected function getStats(): array
    {
        // Total klinik dan RS yang terdaftar
        $totalKlinik = User::whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })->count();

        // Klinik yang sudah diverifikasi
        $klinikVerified = User::whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })->where('verifikasi_akun', 'Diterima')->count();

        // Total izin operasional
        $totalIzin = Izin::count();

        // Izin yang menunggu verifikasi
        $izinMenunggu = Izin::whereDoesntHave('izinDokumen')
            ->orWhereHas('izinDokumen', function ($query) {
                $query->where('hasil', 0); // 0 = Belum Diverifikasi
            })->count();

        // Data ICV dan PNBP
        $icvData = $this->getIcvData();
        $pnbpData = $this->getPnbpData();

        return [
            Stat::make('Total Klinik/RS', $totalKlinik)
                ->description($klinikVerified . ' telah diverifikasi')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart($this->getKlinikChartData()),

            Stat::make('Izin Klinik', $totalIzin)
                ->description($izinMenunggu . ' menunggu verifikasi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->chart($this->getIzinChartData()),

            Stat::make('ICV Dialokasikan', number_format($icvData['total_dialokasikan']))
                ->description(number_format($icvData['dengan_sbbk']) . ' dengan SBBK')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success')
                ->chart($icvData['chart']),

            Stat::make('Pendapatan PNBP', 'Rp ' . number_format($pnbpData['total'] / 1000000, 1) . 'M')
                ->description('Rp ' . number_format($pnbpData['tahun_ini'] / 1000000, 1) . 'M tahun ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->chart($pnbpData['chart']),
        ];
    }

    private function getIcvData(): array
    {
        // Total ICV yang dialokasikan dari tabel alokasi
        $totalDialokasikan = IcvAlokasi::sum('icv_disetujui');

        // ICV yang sudah mendapat SBBK
        $denganSbbk = DB::table('tbl_icv_alokasi')
            ->join('tbl_icv_sbbk', 'tbl_icv_alokasi.id_icv', '=', 'tbl_icv_sbbk.id_icv')
            ->whereNotNull('tbl_icv_sbbk.file_sbbk')
            ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
            ->sum('tbl_icv_alokasi.icv_disetujui');

        // Chart data untuk 6 bulan terakhir (ICV dengan SBBK)
        // Karena tidak ada created_at, kita gunakan data statis atau dari tabel lain
        $chart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);

            // Menggunakan data dari tabel icv untuk chart karena ada tanggal_pengajuan
            $count = DB::table('tbl_icv')
                ->join('tbl_icv_alokasi', 'tbl_icv.id', '=', 'tbl_icv_alokasi.id_icv')
                ->join('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
                ->whereNotNull('tbl_icv_sbbk.file_sbbk')
                ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
                ->whereMonth('tbl_icv.tanggal_pengajuan', $month->month)
                ->whereYear('tbl_icv.tanggal_pengajuan', $month->year)
                ->sum('tbl_icv_alokasi.icv_disetujui');

            $chart[] = $count;
        }

        return [
            'total_dialokasikan' => $totalDialokasikan,
            'dengan_sbbk' => $denganSbbk,
            'chart' => $chart,
        ];
    }

    private function getPnbpData(): array
    {
        // Data PNBP untuk 6 bulan terakhir berdasarkan ICV alokasi dengan SBBK
        $chart = [];
        $tahunIni = 0;

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);

            $icvMonth = DB::table('tbl_icv')
                ->join('tbl_icv_alokasi', 'tbl_icv.id', '=', 'tbl_icv_alokasi.id_icv')
                ->join('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
                ->whereNotNull('tbl_icv_sbbk.file_sbbk')
                ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
                ->whereMonth('tbl_icv.tanggal_pengajuan', $month->month)
                ->whereYear('tbl_icv.tanggal_pengajuan', $month->year)
                ->sum('tbl_icv_alokasi.icv_disetujui');

            $pendapatanMonth = $icvMonth * self::TARIF_ICV;
            $chart[] = $pendapatanMonth / 1000000; // Konversi ke jutaan

            if ($month->year == now()->year) {
                $tahunIni += $pendapatanMonth;
            }
        }

        // Total keseluruhan dari ICV alokasi yang sudah mendapat SBBK
        $totalIcvSbbk = DB::table('tbl_icv_alokasi')
            ->join('tbl_icv_sbbk', 'tbl_icv_alokasi.id_icv', '=', 'tbl_icv_sbbk.id_icv')
            ->whereNotNull('tbl_icv_sbbk.file_sbbk')
            ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
            ->sum('tbl_icv_alokasi.icv_disetujui');

        $totalPendapatan = $totalIcvSbbk * self::TARIF_ICV;

        return [
            'total' => $totalPendapatan,
            'tahun_ini' => $tahunIni,
            'chart' => $chart,
        ];
    }

    private function getKlinikChartData(): array
    {
        // Data klinik terdaftar per bulan untuk 6 bulan terakhir
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = User::whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->whereMonth('created_at', $month->month) // Gunakan created_at untuk User
            ->whereYear('created_at', $month->year)
            ->count();

            $chartData[] = $count;
        }
        return $chartData;
    }

    private function getIzinChartData(): array
    {
        // Data izin diajukan per bulan untuk 6 bulan terakhir
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = Izin::whereMonth('tanggal_pengajuan', $month->month)
                ->whereYear('tanggal_pengajuan', $month->year)
                ->count();

            $chartData[] = $count;
        }
        return $chartData;
    }
}
