<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'tanggal_perbaikan',
        'riwayat_perbaikan', // Tambahkan kolom baru
        'date_updated',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'tanggal_surat' => 'date',
        'tanggal_perbaikan' => 'datetime',
        'riwayat_perbaikan' => 'array', // Cast JSON ke array
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
     * Tambahkan catatan perbaikan ke riwayat
     */
    public function tambahCatatanPerbaikan(string $catatan): void
    {
        $riwayat = $this->riwayat_perbaikan ?? [];

        $riwayat[] = [
            'tanggal' => Carbon::now()->format('Y-m-d H:i:s'),
            'catatan' => $catatan,
        ];

        $this->riwayat_perbaikan = $riwayat;
        $this->save();
    }

    /**
     * Get riwayat perbaikan terakhir
     */
    public function getCatatanTerakhir(): ?array
    {
        $riwayat = $this->riwayat_perbaikan ?? [];
        return empty($riwayat) ? null : end($riwayat);
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
