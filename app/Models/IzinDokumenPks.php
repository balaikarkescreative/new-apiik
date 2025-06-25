<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinDokumenPks extends Model
{
    use HasFactory;

    protected $table = 'tbl_izin_dokumen_pks';

    protected $fillable = [
        'id',
        'id_izin',
        'dokumen_pks_klinik',
        'dokumen_pks_bkk',
        'status_dokumen',
        'tanggal_visitasi',
        'date_updated'
    ];

    protected $casts = [
        'tanggal_visitasi' => 'date',
        'date_updated' => 'datetime',
        'id_izin' => 'integer'
    ];

    public $timestamps = false;

    // Relationship with Izin model
    public function izin()
    {
        return $this->belongsTo(Izin::class, 'id_izin', 'id');
    }

    // Accessor for status_dokumen display
    // public function getStatusDokumenLabelAttribute()
    // {
    //     return match($this->status_dokumen) {
    //         'sign both' => 'Ditandatangani Kedua Pihak',
    //         'not sign' => 'Belum Ditandatangani',
    //         default => $this->status_dokumen
    //     };
    // }
}
