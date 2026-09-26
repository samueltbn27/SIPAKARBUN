<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AturanCf — rule Certainty Factor yang menghubungkan satu
 * Penyakit dengan satu Gejala, beserta nilai keyakinan pakar (cf_pakar).
 *
 * Ini yang dikonsumsi mesin diagnosis Mahasiswa 2 (lewat API, bukan
 * akses tabel langsung — lihat §23.2 API contract di PRD).
 *
 * created_by / updated_by menyimpan users.id TANPA relasi Eloquent
 * belongsTo ke model User di sini, karena kepemilikan tabel `users`
 * ada di modul shared/auth, bukan Mahasiswa 1 (lihat catatan di
 * migration). Kalau perlu data user (nama pengubah terakhir dsb.),
 * query manual: User::find($aturanCf->updated_by).
 */
class AturanCf extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    /**
     * Jenis sumber nilai CF (allowlist terkontrol — revisi Disbun §10).
     * Jangan memakai string arbitrer di luar daftar ini.
     */
    public const SOURCE_SIMULATION = 'simulation';

    public const SOURCE_EXPERT = 'expert';

    public const SOURCE_LITERATURE = 'literature';

    public const SOURCE_EXPERT_LITERATURE = 'expert_and_literature';

    public const SOURCE_TECHNICAL_GUIDELINE = 'technical_guideline';

    public const SOURCE_RESEARCH = 'research';

    public const SOURCE_TYPES = [
        self::SOURCE_SIMULATION,
        self::SOURCE_EXPERT,
        self::SOURCE_LITERATURE,
        self::SOURCE_EXPERT_LITERATURE,
        self::SOURCE_TECHNICAL_GUIDELINE,
        self::SOURCE_RESEARCH,
    ];

    /** Label ramah-UI untuk jenis sumber (revisi Disbun §29). */
    public const SOURCE_LABELS = [
        self::SOURCE_SIMULATION => 'Simulasi / UAT',
        self::SOURCE_EXPERT => 'Pakar',
        self::SOURCE_LITERATURE => 'Literatur',
        self::SOURCE_EXPERT_LITERATURE => 'Pakar + Literatur',
        self::SOURCE_TECHNICAL_GUIDELINE => 'Pedoman Teknis',
        self::SOURCE_RESEARCH => 'Penelitian Terdahulu',
    ];

    public const SIMULATION_JUSTIFICATION = 'Nilai CF digunakan untuk kebutuhan simulasi dan pengujian sistem serta belum merupakan hasil validasi pakar lapangan.';

    /** Status validasi pakar (revisi Disbun §14 — sederhana). */
    public const VALIDATION_UNVALIDATED = 'unvalidated';

    public const VALIDATION_VALIDATED = 'validated';

    public const VALIDATION_LABELS = [
        self::VALIDATION_UNVALIDATED => 'Belum Divalidasi',
        self::VALIDATION_VALIDATED => 'Tervalidasi',
    ];

    protected $table = 'aturan_cf';

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected $fillable = [
        'penyakit_id',
        'gejala_id',
        'cf_pakar',
        'jenis_sumber',
        'sumber',
        'pendekatan',
        'dasar_penentuan',
        'referensi_penulis',
        'referensi_tahun',
        'referensi_url',
        'validator_nama',
        'validator_instansi',
        'tanggal_validasi',
        'status_validasi',
        'status',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cf_pakar' => 'decimal:3',
        'version' => 'integer',
        'tanggal_validasi' => 'date',
    ];

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class);
    }

    public function gejala(): BelongsTo
    {
        return $this->belongsTo(Gejala::class);
    }

    /** Label ramah-UI untuk jenis sumber aturan ini. */
    public function jenisSumberLabel(): string
    {
        return self::SOURCE_LABELS[$this->jenis_sumber] ?? 'Belum tersedia';
    }

    /** Label ramah-UI untuk status validasi aturan ini. */
    public function statusValidasiLabel(): string
    {
        return self::VALIDATION_LABELS[$this->status_validasi ?? self::VALIDATION_UNVALIDATED]
            ?? 'Belum Divalidasi';
    }

    /**
     * Scope: hanya rule berstatus aktif — inilah yang boleh dipakai
     * mesin diagnosis Mahasiswa 2 (M1-FR-009).
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
