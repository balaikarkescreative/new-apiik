<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinPersetujuan extends Model
{
    use HasFactory;

    protected $table = 'tbl_izin_persetujuan';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_izin',
        'file_persetujuan',
        'status',
        'date_updated'
    ];

    protected $casts = [
        'date_updated' => 'datetime',
        'status' => 'integer',
        'id_izin' => 'integer'
    ];

    // Relationship with Izin model
    public function izin()
    {
        return $this->belongsTo(Izin::class, 'id_izin', 'id');
    }
}
