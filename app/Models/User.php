<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'username',
        'latitude',
        'longitude',
        'kategori',
        'alamat',
        'kotakab',
        'pimpinan',
        'pj_akun',
        'kontak_pj',
        'nomor_surat_izin_operasional',
        'masa_berlaku_surat_izin_operasional',
        'pertanyaan1',
        'pertanyaan2',
        'verifikasi_akun',
        'status_akun',
        'is_blocked',
        'date_updated',
        'simply_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the ICV submissions for the user.
     */
    public function icvs()
    {
        return $this->hasMany(Icv::class, 'user_id');
    }

    public function izins()
    {
        return $this->hasMany(Izin::class, 'user_id');
    }

    public function laporanGeotagging()
    {
        return $this->hasOne(LaporanGeotagging::class);
    }

    public function linkSosialisasi()
    {
        return $this->hasOne(LinkSosialisasi::class);
    }

    public function paktaIntegritas()
    {
        return $this->hasOne(PaktaIntegritas::class);
    }

    public function suratPersetujuanIcv()
    {
        return $this->hasOne(SuratPersetujuanIcv::class, 'id_akun', 'id');
    }

    public function simplyUser()
    {
        return $this->belongsTo(SimplyUser::class, 'simply_user_id', 'id_user');
    }
}
