<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Gejala — daftar gejala yang bisa dipilih saat diagnosis
 * (diagnosis sendiri dieksekusi di modul Mahasiswa 2, model ini hanya
 * menyediakan data master gejalanya).
 */
class Gejala extends Model
{
    use HasFactory;

    protected $table = 'gejala';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function aturanCf(): HasMany
    {
        return $this->hasMany(AturanCf::class);
    }

    /**
     * Scope: hanya gejala berstatus aktif/published.
     */
    public function scopeAktifSaja(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
