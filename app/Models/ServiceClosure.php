<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'is_closed',
        'closure_reason',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    // Service types available
    public const SERVICE_TYPES = [
        'icv' => 'Pengajuan ICV',
        'izin' => 'Pengajuan Izin', 
        'account' => 'Pengajuan Akun',
    ];

    // Static methods
    public static function isServiceClosed(string $serviceType): bool
    {
        $closure = static::where('service_type', $serviceType)->first();
        return $closure ? $closure->is_closed : false;
    }

    public static function closeService(string $serviceType, ?string $reason = null): self
    {
        return static::updateOrCreate(
            ['service_type' => $serviceType],
            [
                'is_closed' => true,
                'closure_reason' => $reason,
            ]
        );
    }

    public static function openService(string $serviceType): self
    {
        return static::updateOrCreate(
            ['service_type' => $serviceType],
            [
                'is_closed' => false,
                'closure_reason' => null,
            ]
        );
    }

    public static function getServiceStatus(string $serviceType): ?self
    {
        return static::where('service_type', $serviceType)->first();
    }

    public static function getAllClosedServices(): array
    {
        return static::where('is_closed', true)
            ->pluck('service_type')
            ->toArray();
    }

    // Instance methods
    public function getServiceNameAttribute(): string
    {
        return static::SERVICE_TYPES[$this->service_type] ?? $this->service_type;
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_closed ? 'Tutup' : 'Buka';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_closed ? 'danger' : 'success';
    }
}