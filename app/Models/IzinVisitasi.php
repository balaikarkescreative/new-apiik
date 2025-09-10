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
        'date_updated',
    ];

    public $timestamps = false;

    protected $casts = [
        'date_updated' => 'datetime',
        'tanggal_visitasi' => 'date',
    ];

    public function izin()
    {
        return $this->belongsTo(Izin::class, 'id_izin', 'id');
    }
}
