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
        'tanggal_surat' => 'date',
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

    /**
     * Accessor untuk status masa berlaku surat
     */
    public function getStatusMasaBerlakuAttribute()
    {
        if (! $this->masa_berlaku) {
            return 'Tidak Ada Data';
        }

        $today = now()->toDateString();
        $masaBerlaku = $this->masa_berlaku->toDateString();

        if ($masaBerlaku < $today) {
            return 'Kadaluarsa';
        } elseif ($masaBerlaku <= now()->addDays(30)->toDateString()) {
            return 'Akan Habis';
        } else {
            return 'Aktif';
        }
    }

    /**
     * Scope untuk surat yang masih berlaku
     */
    public function scopeMasihBerlaku($query)
    {
        return $query->where('masa_berlaku', '>=', now());
    }

    /**
     * Scope untuk surat yang akan habis masa berlakunya
     */
    public function scopeAkanHabis($query, $days = 30)
    {
        return $query->whereBetween('masa_berlaku', [now(), now()->addDays($days)]);
    }

    public function scopeKadaluarsa($query)
    {
        return $query->where('masa_berlaku', '<', now());
    }
}
