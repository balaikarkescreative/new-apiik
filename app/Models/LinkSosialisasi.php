<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkSosialisasi extends Model
{
    use HasFactory;

    protected $table = 'link_sosialisasi';

    protected $fillable = [
        'user_id',
        'link',
    ];

    /**
     * Get the user that owns the link sosialisasi.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
