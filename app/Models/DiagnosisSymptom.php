<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DiagnosisSymptom — salah satu gejala yang dipilih user dalam
 * sebuah transaksi diagnosis.
 *
 * `symptom_id` menunjuk ke gejala.id milik Knowledge Management Mahasiswa 1.
 * TIDAK ada relasi Eloquent belongsTo ke model Gejala di sini, karena
 * kepemilikan tabel `gejala` ada di modul Mahasiswa 1 — modul M2 membaca
 * knowledge hanya lewat API (§23.1 PRD). `symptom_name_snapshot` menyimpan
 * nama gejala pada saat diagnosis berlangsung agar riwayat tetap utuh walau
 * data knowledge berubah.
 *
 * `cf_user` = tingkat keyakinan user atas gejala (0.0–1.0, default 1.0),
 * dipakai menghitung CF_gejala = CF_user × CF_pakar (kontrak M2 §6).
 *
 * Baris bersifat append-only: hanya ada created_at, tidak ada updated_at.
 */
class DiagnosisSymptom extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'diagnosis_symptoms';

    protected $fillable = [
        'diagnosis_id',
        'symptom_id',
        'symptom_name_snapshot',
        'cf_user',
    ];

    protected $casts = [
        'symptom_id' => 'integer',
        'cf_user' => 'float',
    ];

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }
}
