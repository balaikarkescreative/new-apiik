<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Widgets/AdminPnbpChartWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Icv;
use App\Models\IcvAlokasi;
use App\Models\IcvSbbk;
use Illuminate\Support\Facades\DB;

class AdminPnbpChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Pendapatan PNBP ICV Per Tahun';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    // Tarif ICV berdasarkan regulasi (dalam rupiah)
    private const TARIF_ICV = 25000; // Rp 25.000 per ICV

    protected function getData(): array
    {
        // Ambil data dari tahun 2023 sampai tahun saat ini
        $currentYear = now()->year;
        $startYear = 2023;
        $years = range($startYear, $currentYear);

        $labels = [];
        $pendapatanData = [];

        foreach ($years as $year) {
            $labels[] = (string) $year;

            // ICV yang sudah mendapat file SBBK per tahun
            $icvDenganSbbk = DB::table('tbl_icv')
                ->join('tbl_icv_alokasi', 'tbl_icv.id', '=', 'tbl_icv_alokasi.id_icv')
                ->join('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
                ->whereNotNull('tbl_icv_sbbk.file_sbbk')
                ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
                ->whereYear('tbl_icv.tanggal_pengajuan', $year)
                ->sum('tbl_icv_alokasi.icv_disetujui');

            // Hitung pendapatan PNBP (ICV dengan SBBK x tarif)
            $pendapatan = $icvDenganSbbk * self::TARIF_ICV;

            $pendapatanData[] = $pendapatan / 1000000; // Konversi ke jutaan rupiah
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan PNBP (Jutaan Rupiah)',
                    'data' => $pendapatanData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => 'rgb(255, 255, 255)',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                    'cornerRadius' => 6,
                    'padding' => 12,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Pendapatan (Jutaan Rupiah)',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.1)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Tahun',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'intersect' => false,
            ],
            'hover' => [
                'mode' => 'nearest',
                'intersect' => false,
            ],
        ];
    }

    public function getDescription(): ?string
    {
        // Total pendapatan dari ICV yang sudah mendapat SBBK (sejak 2023)
        $totalIcvSbbk = DB::table('tbl_icv')
            ->join('tbl_icv_alokasi', 'tbl_icv.id', '=', 'tbl_icv_alokasi.id_icv')
            ->join('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
            ->whereNotNull('tbl_icv_sbbk.file_sbbk')
            ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
            ->whereYear('tbl_icv.tanggal_pengajuan', '>=', 2023)
            ->sum('tbl_icv_alokasi.icv_disetujui');

        $totalPendapatan = $totalIcvSbbk * self::TARIF_ICV;

        // Pendapatan tahun ini
        $icvTahunIni = DB::table('tbl_icv')
            ->join('tbl_icv_alokasi', 'tbl_icv.id', '=', 'tbl_icv_alokasi.id_icv')
            ->join('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
            ->whereNotNull('tbl_icv_sbbk.file_sbbk')
            ->where('tbl_icv_sbbk.file_sbbk', '!=', '')
            ->whereYear('tbl_icv.tanggal_pengajuan', now()->year)
            ->sum('tbl_icv_alokasi.icv_disetujui');

        $pendapatanTahunIni = $icvTahunIni * self::TARIF_ICV;

        // Format currency Indonesia
        $totalFormatted = $this->formatCurrencyID($totalPendapatan);
        $tahunIniFormatted = $this->formatCurrencyID($pendapatanTahunIni);

        return "💰 Total sejak 2023: {$totalFormatted} | 📊 Tahun ini: {$tahunIniFormatted}";
    }

    /**
     * Format currency menggunakan format Indonesia
     */
    private function formatCurrencyID($amount): string
    {
        if ($amount >= 1000000000) {
            // Milyar
            return 'Rp ' . number_format($amount / 1000000000, 1, ',', '.') . ' M';
        } elseif ($amount >= 1000000) {
            // Juta
            return 'Rp ' . number_format($amount / 1000000, 1, ',', '.') . ' Jt';
        } elseif ($amount >= 1000) {
            // Ribu
            return 'Rp ' . number_format($amount / 1000, 0, ',', '.') . ' Rb';
        } else {
            // Satuan
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
    }
}
