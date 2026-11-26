<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimplyUser extends Model
{
    protected $connection = 'simply';

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    public $timestamps = true;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    /**
     * Scope only active employees.
     */
    public function scopeAktif($query)
    {
        return $query->where('status_pegawai_aktif', 'Aktif');
    }

    /**
     * Get display label with name, username and employee status.
     */
    public function getLabelAttribute(): string
    {
        $parts = [$this->nama];
        if (! empty($this->username)) {
            $parts[] = "({$this->username})";
        }

        return implode(' ', $parts);
    }
}
