<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    protected $table = "tbl_izin";

    public $timestamps = false;
    protected $fillable = [
        'id',
        'user_id',
        'kategori_izin',
        'tanggal_pengajuan',
        'nomor_surat',
        'tanggal_surat',
        'file1',
        'file2',
        'file3',
        'file4',
        'file5',
        'file6',
        'file7',
        'file8',
        'file9',
        'file10',
        'file11',
        'file12',
        'file13',
        'file14',
        'status',
        'date_updated',
    ];
    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'tanggal_surat' => 'date',
        'date_updated' => 'datetime',
    ];

    /**
     * Get the user that owns the Izin.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the izin dokumen for this izin.
     */
    public function izinDokumen()
    {
        return $this->hasOne(IzinDokumen::class, 'izin_id');
    }

    public function izinPersetujuan()
    {
        return $this->hasOne(IzinPersetujuan::class, 'id_izin');
    }

    public function izinVisitasi()
    {
        return $this->hasOne(IzinVisitasi::class, 'id_izin');
    }

    public function izinDokumenPks()
    {
        return $this->hasOne(IzinDokumenPks::class, 'id_izin');
    }
}
