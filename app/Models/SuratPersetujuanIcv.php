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
        'file_persetujuan',
        'status_approval',
        'catatan_approval',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'masa_berlaku' => 'date',
        'date_updated' => 'datetime',
        'status_approval' => 'integer',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    public function getStatusApprovalLabelAttribute()
    {
        return match ($this->status_approval) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu Approval',
        };
    }

    public function isApproved()
    {
        return $this->status_approval == self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status_approval == self::STATUS_REJECTED;
    }

    public function isPending()
    {
        return $this->status_approval == self::STATUS_PENDING;
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

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
