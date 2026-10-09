<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatBlokirFaskes extends Model
{
    use HasFactory;

    protected $table = 'riwayat_blokir_faskes';

    protected $fillable = [
        'user_id',
        'admin_id',
        'action',
        'alasan',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ACTION_BLOKIR = 'blokir';

    public const ACTION_BUKA_BLOKIR = 'buka_blokir';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_BLOKIR => 'Diblokir',
            self::ACTION_BUKA_BLOKIR => 'Buka Blokir',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionColor(): string
    {
        return match ($this->action) {
            self::ACTION_BLOKIR => 'danger',
            self::ACTION_BUKA_BLOKIR => 'success',
            default => 'gray',
        };
    }
}
