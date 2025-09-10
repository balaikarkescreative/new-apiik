<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaktaIntegritas extends Model
{
    use HasFactory;

    protected $table = 'pakta_integritas';

    protected $fillable = [
        'user_id',
        'file',
    ];

    /**
     * Get the user that owns the pakta integritas.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
