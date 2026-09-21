<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use HasFactory;

    protected $table = 'login_histories';

    protected $fillable = [
        'user_id',
        'name',
        'login_identifier',
        'role',
        'ip_address',
        'city',
        'region',
        'country',
        'country_code',
        'latitude',
        'longitude',
        'address',
        'is_gps',
        'status',
        'failure_reason',
        'device',
        'platform',
        'browser',
        'user_agent',
        'login_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_gps' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLocationDisplayAttribute(): string
    {
        if (! empty($this->address)) {
            return $this->address;
        }

        $parts = array_filter([$this->city, $this->region, $this->country]);

        if (empty($parts)) {
            if ($this->ip_address === '127.0.0.1' || $this->ip_address === '::1' || str_starts_with($this->ip_address ?? '', '192.168.') || str_starts_with($this->ip_address ?? '', '10.')) {
                return 'Lokal / Private Network';
            }

            return 'Tidak Diketahui';
        }

        return implode(', ', $parts);
    }

    public function getGoogleMapsUrlAttribute(): ?string
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return null;
    }

    public function getDeviceDisplayAttribute(): string
    {
        $browser = $this->browser ?: 'Unknown Browser';
        $platform = $this->platform ?: 'Unknown OS';
        $device = $this->device ? " ({$this->device})" : '';

        return "{$browser} on {$platform}{$device}";
    }

    public function scopeSuccess(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeMasterPassword(Builder $query): Builder
    {
        return $query->where('status', 'master_password');
    }

    public function scopeGps(Builder $query): Builder
    {
        return $query->where('is_gps', true);
    }
}
