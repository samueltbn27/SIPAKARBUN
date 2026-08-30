<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DiagnosisResult — satu hasil terduga (penyakit) beserta nilai
 * Certainty Factor dari sebuah transaksi diagnosis.
 *
 * `disease_id` menunjuk ke penyakit.id milik Knowledge Management Mahasiswa 1.
 * Sama seperti DiagnosisSymptom, TIDAK ada relasi Eloquent ke model Penyakit
 * di sini karena kepemilikan tabel `penyakit` ada di modul Mahasiswa 1
 * (§23.1 PRD). `disease_name_snapshot` menjaga riwayat tetap utuh walau
 * data knowledge berubah.
 *
 * `cf_value` = hasil kombinasi CF mesin diagnosis; `ranking` = peringkat
 * (1 = keyakinan tertinggi). `trace_snapshot` = breakdown per-rule yang
 * menjadi input perhitungan (gejala_id, nama, cf_user, cf_pakar,
 * cf_gejala) agar nilai CF dapat ditelusuri (kontrak M2 §6).
 *
 * Baris append-only: hanya created_at.
 */
class DiagnosisResult extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'diagnosis_results';

    protected $fillable = [
        'diagnosis_id',
        'disease_id',
        'disease_name_snapshot',
        'disease_image_url',
        'solution_snapshot',
        'trace_snapshot',
        'cf_value',
        'ranking',
    ];

    protected $casts = [
        'disease_id' => 'integer',
        'cf_value' => 'decimal:3',
        'ranking' => 'integer',
        'solution_snapshot' => 'array',
        'trace_snapshot' => 'array',
    ];

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }
}
