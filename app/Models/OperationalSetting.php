<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OperationalSetting extends Model
{
    protected $fillable = [
        'is_active', 
        'start_time', 
        'end_time', 
        'operational_days', 
        'icv_closed',
        'izin_closed', 
        'account_closed'
    ];

    protected $casts = [
        'operational_days' => 'json',
        'is_active' => 'boolean',
        'icv_closed' => 'boolean',
        'izin_closed' => 'boolean',
        'account_closed' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        // Event ketika data akan disimpan
        static::saving(function ($model) {
            // Jika pengaturan ini diaktifkan
            if ($model->is_active) {
                // Nonaktifkan semua pengaturan lain
                static::where('id', '!=', $model->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    public static function dayOptions()
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }

    public function getOperationalDaysAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    public function isOpen()
    {
        $now = Carbon::now();
        $currentDay = $now->dayOfWeekIso;
        $currentTime = $now->format('H:i:s');
        $days = $this->operational_days ?: [1, 2, 3, 4, 5];

        if (!is_array($days)) {
            $days = [1, 2, 3, 4, 5];
        }

        return in_array($currentDay, $days) &&
               $currentTime >= $this->start_time &&
               $currentTime <= $this->end_time;
    }

    // Method untuk mendapatkan pengaturan yang sedang aktif
    public static function getActiveSettings()
    {
        return static::where('is_active', true)->first();
    }

    

    // Method untuk cek apakah layanan tertentu ditutup
    public static function isServiceClosed($service)
    {
        $activeSettings = static::getActiveSettings();
        if (!$activeSettings) {
            return false;
        }

        // Cek berdasarkan kolom spesifik saja
        switch ($service) {
            case 'icv':
                return $activeSettings->icv_closed;
            case 'izin':
                return $activeSettings->izin_closed;
            case 'account':
                return $activeSettings->account_closed;
            default:
                return false;
        }
    }

    // Method untuk menutup layanan tertentu
    public static function closeService($service, $reason = null)
    {
        $activeSettings = static::getActiveSettings();
        if (!$activeSettings) {
            return null;
        }

        // Update kolom spesifik berdasarkan layanan
        switch ($service) {
            case 'icv':
                $activeSettings->update(['icv_closed' => true]);
                break;
            case 'izin':
                $activeSettings->update(['izin_closed' => true]);
                break;
            case 'account':
                $activeSettings->update(['account_closed' => true]);
                break;
        }

        return $activeSettings;
    }

    // Method untuk membuka layanan tertentu
    public static function openService($service)
    {
        $activeSettings = static::getActiveSettings();
        if (!$activeSettings) {
            return null;
        }

        // Update kolom spesifik berdasarkan layanan
        switch ($service) {
            case 'icv':
                $activeSettings->update(['icv_closed' => false]);
                break;
            case 'izin':
                $activeSettings->update(['izin_closed' => false]);
                break;
            case 'account':
                $activeSettings->update(['account_closed' => false]);
                break;
        }

        return $activeSettings;
    }
}
