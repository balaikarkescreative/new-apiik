<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Izin;
use Filament\Models\Contracts\FilamentUser;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
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
        'status_akun',
        'date_updated',
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
}
