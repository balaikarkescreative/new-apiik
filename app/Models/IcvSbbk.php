<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcvSbbk extends Model
{
    protected $table = "tbl_icv_sbbk";

    protected $fillable = ["id_icv", "file_sbbk", "sinkarkes", "date_updated"];

    protected $casts = [
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
}
