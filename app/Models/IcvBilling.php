<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcvBilling extends Model
{
    protected $table = "tbl_icv_billing";

    protected $fillable = [
        "id",
        "id_icv",
        "kode_billing",
        "nominal",
        "status",
        "bayar",
        "date_updated",
    ];

    protected $casts = [
        "kode_billing" => "integer",
        "status" => "integer",
        "date_updated" => "datetime",
    ];

    public const UPDATED_AT = "date_updated";
    public const CREATED_AT = null;

    /**
     * Relationship ke Icv
     */
    public function icv(): BelongsTo
    {
        return $this->belongsTo(Icv::class, "id_icv", "id");
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
