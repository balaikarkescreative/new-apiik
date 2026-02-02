<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IzinDokumen extends Model
{
    public $timestamps = false;

    protected $table = 'tbl_izin_dokumen';

    protected $fillable = [
        'izin_id',
        'hasil',
        'catatan',
        'status',
        'date_updated',
    ];

    protected $casts = [
        'date_updated' => 'datetime',
        'hasil' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get the user that owns the Izin.
     */
    protected static function booted()
    {
        static::updating(function ($model) {
            $model->date_updated = now();
        });
    }

    public function izin()
    {
        return $this->belongsTo(Izin::class, 'izin_id');
    }

    public function getHasilLabel()
    {
        return match ($this->hasil) {
            0 => 'Belum Diverifikasi',
            1 => 'Berkas Lengkap',
            2 => 'Berkas Tidak Lengkap',
            3 => 'Ditolak',
            default => 'Belum Diverifikasi',
        };
    }
}
