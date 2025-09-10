<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcvCekDokumen extends Model
{
    protected $table = 'tbl_icv_cek_dokumen';

    protected $fillable = [
        'id',
        'id_icv',
        'hasil_verif',
        'stok_vaksin',
        'jumlah_icv',
        'catatan',
        'status',
        'date_updated',
    ];

    protected $casts = [
        'stok_vaksin' => 'integer',
        'jumlah_icv' => 'integer',
        'status' => 'integer',
        'date_updated' => 'datetime',
    ];

    public const UPDATED_AT = 'date_updated';

    public const CREATED_AT = null;

    /**
     * Relationship ke Icv
     */
    public function icv(): BelongsTo
    {
        return $this->belongsTo(Icv::class, 'id_icv', 'id');
    }

    /**
     * Boot method untuk auto update timestamp
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->date_updated = now();
        });
    }
}
