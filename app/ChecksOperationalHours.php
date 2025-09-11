<?php

namespace App;

use App\Models\OperationalSetting;
use App\Models\ServiceClosure;
use Filament\Notifications\Notification;

trait ChecksOperationalHours
{
    /**
     * Check if operational hours allow access
     */
    protected function checkOperationalHours(string $serviceType = 'default'): bool
    {
        $setting = OperationalSetting::getActiveSettings();

        // Jika tidak ada pengaturan aktif, izinkan akses (tidak ada pembatasan)
        if (! $setting || ! $setting->is_active) {
            return true;
        }

        // Cek apakah layanan spesifik ditutup
        if ($this->isServiceSpecificallyClosed($setting, $serviceType)) {
            $message = $this->buildOperationalMessage($setting, $serviceType);

            $this->sendOperationalNotification(
                'Layanan Ditutup Sementara',
                $message,
                'warning'
            );

            return false;
        }

        // Jika ada pengaturan aktif, cek jam operasional
        if (! $setting->isOpen()) {
            $message = $this->buildOperationalMessage($setting, $serviceType);

            $this->sendOperationalNotification(
                'Layanan Tutup',
                $message,
                'warning'
            );

            return false;
        }

        return true;
    }

    /**
     * Check operational hours and redirect if necessary
     */
    protected function checkOperationalHoursWithRedirect(string $serviceType = 'default', string $redirectTo = '/'): bool
    {
        $setting = OperationalSetting::getActiveSettings();

        // Jika tidak ada pengaturan aktif, izinkan akses
        if (! $setting || ! $setting->is_active) {
            return true;
        }

        // Cek apakah layanan spesifik ditutup
        if ($this->isServiceSpecificallyClosed($setting, $serviceType)) {
            $message = $this->buildOperationalMessage($setting, $serviceType);

            $this->sendOperationalNotification(
                'Layanan Ditutup Sementara',
                $message,
                'warning'
            );
            $this->redirectToRoute($redirectTo);

            return false;
        }

        // Jika ada pengaturan aktif, cek jam operasional
        if (! $setting->isOpen()) {
            $message = $this->buildOperationalMessage($setting, $serviceType);

            $this->sendOperationalNotification(
                'Layanan Tutup',
                $message,
                'warning'
            );
            $this->redirectToRoute($redirectTo);

            return false;
        }

        return true;
    }

    /**
     * Check operational hours and throw exception if necessary
     */
    protected function checkOperationalHoursWithException(string $serviceType = 'default'): void
    {
        $setting = OperationalSetting::getActiveSettings();

        // Jika tidak ada pengaturan aktif, izinkan akses
        if (! $setting || ! $setting->is_active) {
            return;
        }

        // Cek apakah layanan spesifik ditutup
        if ($this->isServiceSpecificallyClosed($setting, $serviceType)) {
            $message = $this->buildOperationalMessage($setting, $serviceType);
            throw new \Exception($message);
        }

        // Jika ada pengaturan aktif, cek jam operasional
        if (! $setting->isOpen()) {
            $message = $this->buildOperationalMessage($setting, $serviceType);
            throw new \Exception($message);
        }
    }

    /**
     * Get operational status for display
     */
    protected function getOperationalStatus(string $serviceType = 'default'): array
    {
        $setting = OperationalSetting::getActiveSettings();

        if (! $setting || ! $setting->is_active) {
            return [
                'status' => 'no_restriction',
                'message' => 'Tidak ada pembatasan jam operasional',
                'color' => 'success',
            ];
        }

        // Cek apakah layanan spesifik ditutup
        if ($this->isServiceSpecificallyClosed($setting, $serviceType)) {
            return [
                'status' => 'service_closed',
                'message' => $this->buildOperationalMessage($setting, $serviceType),
                'color' => 'danger',
            ];
        }

        if (! $setting->isOpen()) {
            return [
                'status' => 'closed',
                'message' => $this->buildOperationalMessage($setting, $serviceType),
                'color' => 'warning',
            ];
        }

        return [
            'status' => 'open',
            'message' => 'Layanan sedang beroperasi',
            'color' => 'success',
        ];
    }

    /**
     * Build operational message
     */
    private function buildOperationalMessage(OperationalSetting $setting, string $serviceType = 'default'): string
    {
        // Jika layanan spesifik ditutup
        if ($this->isServiceSpecificallyClosed($setting, $serviceType)) {
            return sprintf(
                '%s sedang ditutup sementara oleh administrator.',
                $this->getServiceName($serviceType)
            )."\n\nSilakan coba lagi nanti atau hubungi administrator untuk informasi lebih lanjut.";
        }

        // Pesan normal untuk jam operasional
        $dayNames = [];
        if (! empty($setting->operational_days)) {
            foreach ($setting->operational_days as $day) {
                $dayNames[] = OperationalSetting::dayOptions()[$day] ?? "Hari-$day";
            }
        }

        $operationalDays = ! empty($dayNames) ? implode(', ', $dayNames) : 'Setiap hari';

        return sprintf(
            '%s hanya dapat dilakukan pada %s, jam %s - %s.',
            $this->getServiceName($serviceType),
            $operationalDays,
            date('H:i', strtotime($setting->start_time)),
            date('H:i', strtotime($setting->end_time))
        );
    }

    /**
     * Check if specific service is closed
     */
    private function isServiceSpecificallyClosed(OperationalSetting $setting, string $serviceType): bool
    {
        // Map service type untuk compatibility
        $mappedServiceType = match ($serviceType) {
            'register' => 'account',
            default => $serviceType
        };

        // Cek penutupan layanan spesifik menggunakan ServiceClosure
        return ServiceClosure::isServiceClosed($mappedServiceType);
    }

    /**
     * Send operational notification
     */
    private function sendOperationalNotification(string $title, string $message, string $type = 'warning'): void
    {
        Notification::make()
            ->title($title)
            ->body($message)
            ->{$type}()
            ->persistent()
            ->send();
    }

    /**
     * Get service name based on type
     */
    private function getServiceName(string $serviceType): string
    {
        return match ($serviceType) {
            'account', 'register' => 'Pengajuan akun baru',
            'izin' => 'Pengajuan izin',
            'icv' => 'Pengajuan ICV',
            'default' => 'Layanan ini'
        };
    }

    /**
     * Redirect to specific route
     */
    private function redirectToRoute(string $route): void
    {
        if (method_exists($this, 'redirect')) {
            $this->redirect($route);
        } else {
            redirect()->to($route);
        }
    }

    /**
     * Check if user can bypass operational hours (e.g., admin)
     */
    protected function canBypassOperationalHours(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->hasRole(['admin, verifikator1, verifikator2, pos']);
    }

    /**
     * Main check method with bypass option
     */
    protected function checkOperationalHoursWithBypass(string $serviceType = 'default'): bool
    {
        // Admin dapat bypass
        if ($this->canBypassOperationalHours()) {
            return true;
        }

        return $this->checkOperationalHours($serviceType);
    }
}
