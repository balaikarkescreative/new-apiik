<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanGeotagging extends Model
{
    use HasFactory;

    protected $table = 'laporan_geotagging';

    protected $fillable = [
        'user_id',
        'google_drive_link',
    ];

    /**
     * Get the user that owns the laporan geotagging.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
