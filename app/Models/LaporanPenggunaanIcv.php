<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPenggunaanIcv extends Model
{
    use HasFactory;

    protected $table = 'tbl_laporan_penggunaan_icv';

    protected $fillable = [
        'id_user',
        'id_jenis_vaksin',
        'tgl_pelaporan',
        'jml_terbit_icv',
        'sisa_icv',
        'jml_guna_vaksin',
        'sisa_vaksin',
        'keterangan',
        'date_updated',
    ];

    public $timestamps = false;

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_user');
    }

    // Relasi ke Vaksin
    public function jenisVaksin()
    {
        return $this->belongsTo(\App\Models\Vaksin::class, 'id_jenis_vaksin');
    }
}
