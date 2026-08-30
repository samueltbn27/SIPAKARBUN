<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model Gejala — daftar gejala yang bisa dipilih saat diagnosis
 * (diagnosis sendiri dieksekusi di modul Mahasiswa 2, model ini hanya
 * menyediakan data master gejalanya).
 */
class Gejala extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $table = 'gejala';

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'image_path',
        'status',
    ];

    public function aturanCf(): HasMany
    {
        return $this->hasMany(AturanCf::class);
    }

    /**
     * Scope: hanya gejala berstatus aktif/terpublikasi.
     */
    public function scopeAktifSaja(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    public function scopeDraftSaja(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeNonaktifSaja(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NONAKTIF);
    }
}
