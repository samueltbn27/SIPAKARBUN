<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model Permohonan Penanganan — ajuan Poktan berdasarkan diagnosis yang
 * sudah tersimpan (kontrak M2 §9).
 *
 * Entitas terpisah dari Kasus: permohonan dinilai Operator UPTD
 * (terima/tolak) sebelum menjadi kasus penanganan.
 *
 * Kepemilikan data:
 * - `kelompok_tani_id` menunjuk ke id SHARED Integration
 *   (ref_kelompok_tani.id) tanpa FK constraint; nama kelompok tani
 *   disimpan sebagai snapshot (kelompok_tani_name_snapshot).
 * - `latitude_kasus`/`longitude_kasus` adalah koordinat KASUS/serangan
 *   (BUKAN koordinat kelompok tani — kontrak §10).
 * - `catatan_pemohon` adalah catatan Poktan; `reviewed_by`/`reviewed_at`
 *   diisi Operator saat mulai review.
 *
 * Status permohonan: diajukan | sedang_direview | diterima | ditolak.
 */
class PermohonanPenanganan extends Model
{
    use HasFactory;

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_SEDANG_DIREVIEW = 'sedang_direview';

    public const STATUS_DITERIMA = 'diterima';

    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'permohonan_penanganan';

    protected $fillable = [
        'permohonan_code',
        'diagnosis_id',
        'kelompok_tani_id',
        'kelompok_tani_name_snapshot',
        'latitude_kasus',
        'longitude_kasus',
        'alamat_kasus',
        'kode_kabupaten',
        'kabupaten',
        'kode_kecamatan',
        'kecamatan',
        'kode_desa',
        'kelurahan',
        'catatan_pemohon',
        'status',
        'reviewed_by',
        'reviewed_at',
        'created_by',
    ];

    protected $casts = [
        'diagnosis_id' => 'integer',
        'kelompok_tani_id' => 'integer',
        'latitude_kasus' => 'float',
        'longitude_kasus' => 'float',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function keputusan(): HasOne
    {
        return $this->hasOne(KeputusanPermohonan::class, 'permohonan_id');
    }

    public function kasus(): HasOne
    {
        return $this->hasOne(KasusPenanganan::class, 'permohonan_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(PermohonanEvidence::class, 'permohonan_id');
    }
}
