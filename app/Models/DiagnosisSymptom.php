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
    ];

    protected $casts = [
        'symptom_id' => 'integer',
    ];

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }
}
