<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'kelompok_tani_id',
        'kelompok_tani_kode',
        'kelompok_tani_nama',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function kelompokTani(): BelongsTo
    {
        return $this->belongsTo(RefKelompokTani::class, 'kelompok_tani_id');
    }

    /**
     * Apakah tautan Poktan akun ini masih tersedia pada referensi Disbun.
     * Memakai relasi yang sudah di-eager-load (tanpa query tambahan).
     */
    public function hasAvailablePoktan(): bool
    {
        $kelompokTani = $this->kelompokTani;

        return $kelompokTani !== null
            && $kelompokTani->source === RefKelompokTani::SOURCE_DISBUN
            && (bool) $kelompokTani->source_is_active
            && (bool) $kelompokTani->is_verified
            && $kelompokTani->sync_status !== RefKelompokTani::SYNC_QUARANTINED
            && $kelompokTani->deleted_at === null;
    }
}
