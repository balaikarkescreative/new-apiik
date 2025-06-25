<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratPersetujuanIcv extends Model
{
    use HasFactory;

    protected $table = 'tbl_surat_persetujuan_icv';
    protected $primaryKey = 'id';
    public $timestamps = false; // Karena hanya ada date_updated

    protected $fillable = [
        'id',
        'id_akun',
        'nomor_surat',
        'tanggal_surat',
        'masa_berlaku',
    ];

    protected $casts = [
        'tanggal_surat'=> 'date',
        'masa_berlaku' => 'date',
        'date_updated' => 'datetime',
    ];

    /**
     * Relationship dengan User/Akun
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_akun', 'id');
    }
}
