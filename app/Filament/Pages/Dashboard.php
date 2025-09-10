<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Pages/Dashboard.php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminPnbpChartWidget;
use App\Filament\Widgets\AdminRecentActivitiesWidget;
use App\Filament\Widgets\AdminStatsWidget;
use App\Filament\Widgets\UserStatsWidget;
use App\Filament\Widgets\UserStatusWidget;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        // parent::mount();
        if (auth()->user()->status_akun === 'Tidak Aktif') {
            auth()->logout(); // Logout user jika status akun tidak aktif

            Notification::make()
                ->title('Akun Tidak Aktif')
                ->body('Akun Anda nonaktif. Silakan hubungi admin untuk informasi lebih lanjut.')
                ->danger()
                ->persistent()
                ->send();

            redirect()->to('/');
        }

        // Set the title of the dashboard page
    }

    public function getWidgets(): array
    {
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('verifikator1') || $user->hasRole('verifikator2') || $user->hasRole('pos')) {
            return [
                AdminStatsWidget::class,
                AdminPnbpChartWidget::class, // Chart pendapatan PNBP
                AdminRecentActivitiesWidget::class,
            ];
        } else {
            return [
                UserStatsWidget::class,
                UserStatusWidget::class,
            ];
        }
    }
}
