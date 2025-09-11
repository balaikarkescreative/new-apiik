<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OperationalSetting extends Model
{
    protected $fillable = [
        'is_active',
        'start_time',
        'end_time',
        'operational_days',
    ];

    protected $casts = [
        'operational_days' => 'json',
        'is_active' => 'boolean',
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

        if (! is_array($days)) {
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
}
