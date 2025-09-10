<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaksinator extends Model
{
    use HasFactory;

    protected $table = 'tbl_vaksinator';

    protected $primaryKey = 'id';

    public $timestamps = false; // Karena hanya ada date_updated

    protected $fillable = [
        'id',
        'id_akun',
        'nama_vaksinator',
        'nomor_izin_praktek',
        'masa_aktif_izin_praktek',
        'nomor_sertifikat_vaksinator',
        'masa_aktif_sertifikat',
        'date_updated',
    ];

    protected $casts = [
        'masa_aktif_izin_praktek' => 'date',
        'masa_aktif_sertifikat' => 'date',
        'date_updated' => 'datetime',
    ];

    /**
     * Relationship dengan User/Akun
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_akun', 'id');
    }

    /**
     * Accessor untuk status masa aktif izin praktek
     */
    public function getStatusIzinPraktekAttribute()
    {
        if (! $this->masa_aktif_izin_praktek) {
            return 'Tidak Ada Data';
        }

        $today = now()->toDateString();
        $masaAktif = $this->masa_aktif_izin_praktek->toDateString();

        if ($masaAktif < $today) {
            return 'Kadaluarsa';
        } elseif ($masaAktif <= now()->addDays(30)->toDateString()) {
            return 'Akan Habis';
        } else {
            return 'Aktif';
        }
    }

    /**
     * Accessor untuk status masa aktif sertifikat
     */
    public function getStatusSertifikatAttribute()
    {
        if (! $this->masa_aktif_sertifikat) {
            return 'Tidak Ada Data';
        }

        $today = now()->toDateString();
        $masaAktif = $this->masa_aktif_sertifikat->toDateString();

        if ($masaAktif < $today) {
            return 'Kadaluarsa';
        } elseif ($masaAktif <= now()->addDays(30)->toDateString()) {
            return 'Akan Habis';
        } else {
            return 'Aktif';
        }
    }

    /**
     * Scope untuk vaksinator aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('masa_aktif_izin_praktek', '>=', now())
            ->where('masa_aktif_sertifikat', '>=', now());
    }

    /**
     * Scope untuk vaksinator yang akan habis masa aktifnya
     */
    public function scopeAkanHabis($query, $days = 30)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereBetween('masa_aktif_izin_praktek', [now(), now()->addDays($days)])
                ->orWhereBetween('masa_aktif_sertifikat', [now(), now()->addDays($days)]);
        });
    }
}
