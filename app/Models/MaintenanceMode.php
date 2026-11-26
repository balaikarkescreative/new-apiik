<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;

/**
 * MaintenanceMode Model
 * 
 * URGENSI FIELD PENTING:
 * 
 * 1. RETRY_AFTER (seconds):
 *    - Memberitahu browser/crawler kapan harus mencoba lagi
 *    - HTTP 503 status akan include "Retry-After" header
 *    - Mencegah spam request selama maintenance
 *    - SEO friendly: search engine tahu ini temporary
 *    - Default 60 detik = balance antara user experience dan server load
 * 
 * 2. MESSAGE:
 *    - Komunikasi transparan dengan user tentang maintenance
 *    - Mengurangi frustasi user dengan info yang jelas
 *    - Bisa include estimasi waktu selesai
 *    - Professional image perusahaan
 *    - Contoh: "Sistem sedang maintenance, estimasi selesai 15:00 WIB"
 * 
 * 3. SECRET_KEY:
 *    - Akses darurat untuk admin/developer
 *    - Monitoring sistem selama maintenance
 *    - Emergency rollback jika ada masalah
 */
class MaintenanceMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'secret_key',
        'message',
        'retry_after',
        'enabled_at',
        'disabled_at'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'retry_after' => 'integer'
    ];

    /**
     * Check if maintenance mode is currently active
     */
    public static function isActive(): bool
    {
        return App::isDownForMaintenance();
    }

    /**
     * Enable maintenance mode
     * 
     * @param string $secretKey - Kata kunci untuk bypass maintenance mode
     * @param string|null $message - Pesan yang ditampilkan ke user (PENTING: Informasi kapan maintenance selesai)
     * @param int $retryAfter - Waktu dalam detik untuk retry (PENTING: Browser/crawler akan menunggu sekian detik sebelum coba lagi)
     */
    public static function enable(string $secretKey, ?string $message = null, int $retryAfter = 60): bool
    {
        try {
            // Simpan data maintenance ke file untuk dibaca saat mode down
            $maintenanceData = [
                'secret_key' => $secretKey,
                'message' => $message,
                'retry_after' => $retryAfter,
                'enabled_at' => now()->toISOString(),
            ];
            
            file_put_contents(
                storage_path('framework/maintenance.json'),
                json_encode($maintenanceData, JSON_PRETTY_PRINT)
            );

            $options = [
                '--secret' => $secretKey,
                '--retry' => $retryAfter,
                '--render' => 'maintenance',
            ];

            Artisan::call('down', $options);

            // Update or create maintenance record
            static::updateOrCreate(
                ['id' => 1],
                [
                    'is_enabled' => true,
                    'secret_key' => $secretKey,
                    'message' => $message,
                    'retry_after' => $retryAfter,
                    'enabled_at' => now(),
                    'disabled_at' => null
                ]
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to enable maintenance mode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable maintenance mode
     */
    public static function disable(): bool
    {
        try {
            Artisan::call('up');

            // Hapus file maintenance data
            $maintenanceFile = storage_path('framework/maintenance.json');
            if (file_exists($maintenanceFile)) {
                unlink($maintenanceFile);
            }

            // Update maintenance record
            static::updateOrCreate(
                ['id' => 1],
                [
                    'is_enabled' => false,
                    'disabled_at' => now()
                ]
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to disable maintenance mode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current maintenance status
     */
    public static function getStatus(): ?self
    {
        return static::find(1);
    }

    /**
     * Get bypass URL
     */
    public function getBypassUrl(): string
    {
        return url('/') . '/' . $this->secret_key;
    }
}
