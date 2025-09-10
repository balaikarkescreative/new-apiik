<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanPemanfaatanIcv extends Model
{
    protected $table = 'tbl_laporan_pemanfaatan_icv';

    protected $fillable = [
        'id_klinik',
        'tanggal',
        'judul_laporan',
        'file',
    ];

    public $timestamps = false;

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_klinik');
    }
}
