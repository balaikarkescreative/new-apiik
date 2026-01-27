<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'permit_type',
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
        'laporan_icv_backdate' => 'Back Date Laporan Harian ICV',
    ];

    // Permit types untuk layanan izin
    public const PERMIT_TYPES = [
        'izin_baru' => 'Izin Baru',
        'izin_perpanjangan' => 'Izin Perpanjangan',
    ];

    // Static methods
    public static function isServiceClosed(string $serviceType, ?string $permitType = null): bool
    {
        // Jika ada permit_type (child service), cek parent dulu
        if ($permitType) {
            // Cek apakah parent service ditutup
            $parentClosure = static::where('service_type', $serviceType)
                ->whereNull('permit_type')
                ->first();
            
            // Jika parent ditutup, return true (child otomatis tertutup)
            if ($parentClosure && $parentClosure->is_closed) {
                return true;
            }
            
            // Jika parent buka, cek child-nya
            $childClosure = static::where('service_type', $serviceType)
                ->where('permit_type', $permitType)
                ->first();
            
            return $childClosure ? $childClosure->is_closed : false;
        }
        
        // Jika tidak ada permit_type (parent service), cek parent saja
        $closure = static::where('service_type', $serviceType)
            ->whereNull('permit_type')
            ->first();
        
        return $closure ? $closure->is_closed : false;
    }

    public static function closeService(string $serviceType, ?string $reason = null, ?string $permitType = null): self
    {
        $attributes = ['service_type' => $serviceType];
        
        if ($permitType) {
            $attributes['permit_type'] = $permitType;
        }
        
        $record = static::updateOrCreate(
            $attributes,
            [
                'is_closed' => true,
                'closure_reason' => $reason,
            ]
        );
        
        // Jika menutup parent (tanpa permit_type), tutup semua children juga
        if (!$permitType && $serviceType === 'izin') {
            static::where('service_type', $serviceType)
                ->whereNotNull('permit_type')
                ->update([
                    'is_closed' => true,
                    'closure_reason' => $reason ?? 'Ditutup karena parent service ditutup',
                ]);
        }
        
        return $record;
    }

    public static function openService(string $serviceType, ?string $permitType = null): self
    {
        $attributes = ['service_type' => $serviceType];
        
        if ($permitType) {
            $attributes['permit_type'] = $permitType;
        }
        
        $record = static::updateOrCreate(
            $attributes,
            [
                'is_closed' => false,
                'closure_reason' => null,
            ]
        );
        
        // Jika membuka parent (tanpa permit_type), buka semua children juga
        if (!$permitType && $serviceType === 'izin') {
            static::where('service_type', $serviceType)
                ->whereNotNull('permit_type')
                ->update([
                    'is_closed' => false,
                    'closure_reason' => null,
                ]);
        }
        
        return $record;
    }

    public static function getServiceStatus(string $serviceType, ?string $permitType = null): ?self
    {
        $query = static::where('service_type', $serviceType);
        
        if ($permitType) {
            $query->where('permit_type', $permitType);
        } else {
            $query->whereNull('permit_type');
        }
        
        return $query->first();
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
        $serviceName = static::SERVICE_TYPES[$this->service_type] ?? $this->service_type;
        
        // Jika ada permit_type, tambahkan ke nama
        if ($this->permit_type) {
            $permitName = static::PERMIT_TYPES[$this->permit_type] ?? $this->permit_type;
            return $serviceName . ' - ' . $permitName;
        }
        
        return $serviceName;
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