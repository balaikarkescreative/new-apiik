<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kotakab extends Model
{
    protected $table = "tbl_kotakab";

    protected $fillable = [
        'nama_kotakab',
        'status',
    ];

    public $timestamps = false;
}
