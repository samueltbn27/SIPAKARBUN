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

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    /**
     * Jenis referensi gejala (allowlist terkontrol — revisi Disbun §8).
     * Jangan mengklaim referensi yang belum tersedia.
     */
    public const REFERENSI_PEDOMAN = 'pedoman';

    public const REFERENSI_LITERATUR = 'literatur';

    public const REFERENSI_JURNAL = 'jurnal';

    public const REFERENSI_PAKAR = 'pakar';

    public const REFERENSI_LAINNYA = 'lainnya';

    public const REFERENSI_JENIS = [
        self::REFERENSI_PEDOMAN,
        self::REFERENSI_LITERATUR,
        self::REFERENSI_JURNAL,
        self::REFERENSI_PAKAR,
        self::REFERENSI_LAINNYA,
    ];

    public const REFERENSI_LABELS = [
        self::REFERENSI_PEDOMAN => 'Pedoman Teknis',
        self::REFERENSI_LITERATUR => 'Literatur',
        self::REFERENSI_JURNAL => 'Jurnal / Penelitian',
        self::REFERENSI_PAKAR => 'Pakar',
        self::REFERENSI_LAINNYA => 'Lainnya',
    ];

    protected $table = 'gejala';

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'kriteria_observasi',
        'metode_pengamatan',
        'referensi_jenis',
        'referensi_judul',
        'referensi_penulis',
        'referensi_tahun',
        'referensi_url',
        'image_path',
        'status',
    ];

    public function referensiJenisLabel(): string
    {
        return self::REFERENSI_LABELS[$this->referensi_jenis] ?? 'Belum tersedia';
    }

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
