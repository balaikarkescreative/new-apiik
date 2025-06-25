<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Widgets/UserStatsWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Izin;
use App\Models\Icv;
use App\Models\LaporanPenggunaanIcv;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        // Total izin operasional user
        $totalIzin = Izin::where('user_id', $userId)->count();

        // Izin yang sudah disetujui
        $izinDisetujui = Izin::where('user_id', $userId)
            ->whereHas('izinPersetujuan', function ($query) {
                $query->where('status', 0); // 0 = Disetujui
            })->count();

        // Total ICV user
        $totalIcv = Icv::where('user_id', $userId)->count();

        // ICV yang diproses adalah icv yang belum memiliki file SBBK
        $icvAktif = Icv::where('user_id', $userId)
            ->whereDoesntHave('icvSbbk', function ($query) {
                $query->whereNotNull('file_sbbk');
            })->count();

        // Total ICV yang telah diterbitkan user
        $icvDiterbitkan = LaporanPenggunaanIcv::where('id_user', $userId)
            ->sum('jml_terbit_icv');

        // ICV bulan ini
        $icvBulanIni = LaporanPenggunaanIcv::where('id_user', $userId)
            ->whereMonth('tgl_pelaporan', now()->month)
            ->whereYear('tgl_pelaporan', now()->year)
            ->sum('jml_terbit_icv');

        return [
            Stat::make('Izin Klinik', $totalIzin)
                ->description($izinDisetujui . ' disetujui')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([0, 1, 2, $totalIzin]),

            Stat::make('ICV Diajukan', $totalIcv)
                ->description($icvAktif . ' aktif')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success')
                ->chart([0, 5, 10, $totalIcv]),

            Stat::make('Total ICV Terbit', $icvDiterbitkan)
                ->description($icvBulanIni . ' bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info')
                ->chart([10, 30, 50, $icvDiterbitkan]),

            Stat::make('Sisa ICV', $this->getSisaIcv($userId))
                ->description('ICV tersedia')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('warning'),
        ];
    }

    private function getSisaIcv($userId): int
    {
        return LaporanPenggunaanIcv::where('id_user', $userId)
            ->latest('tgl_pelaporan')
            ->value('sisa_icv') ?? 0;
    }
}
