<?php
// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Widgets/AdminRecentActivitiesWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Izin;
use App\Models\LaporanPenggunaanIcv;
use App\Models\Icv;
use App\Models\User;

class AdminRecentActivitiesWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-recent-activities';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        // Izin terbaru yang diajukan
        $recentIzin = Izin::with('user')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get();

        // Laporan ICV terbaru (dengan filter untuk menghindari data kosong)
        $recentLaporan = LaporanPenggunaanIcv::with('user')
            ->where('jml_terbit_icv', '>', 0)
            ->latest('tgl_pelaporan')
            ->take(5)
            ->get();

        // ICV terbaru yang diajukan
        $recentIcv = Icv::with('user')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get();

        // Registrasi klinik terbaru
        $recentRegistrations = User::whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })
        ->latest('created_at')
        ->take(5)
        ->get();

        return [
            'recentIzin' => $recentIzin,
            'recentLaporan' => $recentLaporan,
            'recentIcv' => $recentIcv,
            'recentRegistrations' => $recentRegistrations,
        ];
    }
}
