<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Widgets/UserStatusWidget.php

namespace App\Filament\Widgets;

use App\Models\Icv;
use App\Models\Izin;
use App\Models\LaporanPenggunaanIcv;
use Filament\Widgets\Widget;

class UserStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.user-status';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $userId = auth()->id();

        // Status izin terakhir
        $latestIzin = Izin::where('user_id', $userId)
            ->with(['izinDokumen', 'izinPersetujuan'])
            ->latest('tanggal_pengajuan')
            ->first();

        // ICV terbaru
        $latestIcv = Icv::where('user_id', $userId)
            ->with(['icvSbbk'])
            ->latest('tanggal_pengajuan')
            ->first();

        // Laporan ICV terbaru
        $latestLaporan = LaporanPenggunaanIcv::where('id_user', $userId)
            ->latest('tgl_pelaporan')
            ->take(3)
            ->get();

        // Status akun user
        $userStatus = auth()->user();

        return [
            'latestIzin' => $latestIzin,
            'latestIcv' => $latestIcv,
            'latestLaporan' => $latestLaporan,
            'userStatus' => $userStatus,
        ];
    }
}
