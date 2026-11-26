<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IzinVisitasi extends Model
{
    // Nama tabel jika tidak mengikuti konvensi (opsional)
    protected $table = 'tbl_izin_visitasi';

    protected $fillable = [
        'id_izin',
        'tanggal_visitasi',
        'status',
        'catatan',
        'riwayat_visitasi',
        'bukti_perbaikan',
        'tanggal_perbaikan_visitasi',
        'date_updated',
    ];

    public $timestamps = false;

    // Konstanta untuk status visitasi
    const STATUS_MEMENUHI_SYARAT = 'Memenuhi syarat';
    const STATUS_TIDAK_MEMENUHI_SEMENTARA = 'Tidak memenuhi syarat sementara';
    const STATUS_TIDAK_MEMENUHI = 'Tidak memenuhi syarat';

    protected $casts = [
        'date_updated' => 'datetime',
        'tanggal_visitasi' => 'date',
        'tanggal_perbaikan_visitasi' => 'datetime',
        'riwayat_visitasi' => 'array',
    ];

    public function izin()
    {
        return $this->belongsTo(Izin::class, 'id_izin', 'id');
    }

    // Helper method untuk mendapatkan status yang tersedia
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_MEMENUHI_SYARAT,
            self::STATUS_TIDAK_MEMENUHI_SEMENTARA,
            self::STATUS_TIDAK_MEMENUHI,
        ];
    }

    // Accessor untuk cek apakah perlu catatan
    public function getPerluCatatanAttribute()
    {
        return $this->status !== self::STATUS_MEMENUHI_SYARAT;
    }

    // Accessor untuk cek apakah bisa lanjut ke persetujuan
    public function getBisaLanjutPersetujuanAttribute()
    {
        return $this->status === self::STATUS_MEMENUHI_SYARAT;
    }

    // Accessor untuk cek apakah perlu revisi (tidak memenuhi sementara)
    public function getPerluRevisiAttribute()
    {
        return $this->status === self::STATUS_TIDAK_MEMENUHI_SEMENTARA;
    }

    // Accessor untuk cek apakah ditolak permanen
    public function getDitolakAttribute()
    {
        return $this->status === self::STATUS_TIDAK_MEMENUHI;
    }
}
