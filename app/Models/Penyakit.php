<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model Penyakit — inti basis pengetahuan Mahasiswa 1.
 *
 * Relasi:
 * - hasMany Solusi (satu penyakit bisa punya banyak rekomendasi solusi)
 * - hasMany AturanCf (rule CF penyakit ini terhadap berbagai gejala)
 * - hasMany PenyakitKomoditas (komoditas mana saja yang rentan penyakit ini)
 *
 * Catatan: relasi many-to-many ke Gejala SENGAJA tidak dibuat lewat
 * belongsToMany()->using(AturanCf::class), karena AturanCf adalah entitas
 * mandiri (punya audit trail sendiri: created_by, updated_by, version),
 * bukan sekadar pivot polos. Untuk ambil gejala dari sebuah penyakit,
 * lewati AturanCf: $penyakit->aturanCf()->with('gejala')->get()
 * atau pakai scope aktifSaja() di bawah untuk versi yang sudah dipublikasi.
 */
class Penyakit extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $table = 'penyakit';

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'image_path',
        'status',
    ];

    public function solusi(): HasMany
    {
        return $this->hasMany(Solusi::class);
    }

    public function aturanCf(): HasMany
    {
        return $this->hasMany(AturanCf::class);
    }

    public function penyakitKomoditas(): HasMany
    {
        return $this->hasMany(PenyakitKomoditas::class);
    }

    /**
     * Scope: hanya penyakit berstatus aktif/terpublikasi.
     * Dipakai saat menyediakan data untuk API yang dikonsumsi Mahasiswa 2
     * (M1-FR-009 — knowledge draft/nonaktif tidak boleh terekspos).
     */
    public function scopeAktifSaja(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    /**
     * Scope: knowledge yang masih berupa draft (M1-FR-008).
     */
    public function scopeDraftSaja(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeNonaktifSaja(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NONAKTIF);
    }
}
