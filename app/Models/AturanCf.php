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

    protected $table = 'aturan_cf';

    protected $fillable = [
        'penyakit_id',
        'gejala_id',
        'cf_pakar',
        'is_active',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cf_pakar' => 'decimal:3',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class);
    }

    public function gejala(): BelongsTo
    {
        return $this->belongsTo(Gejala::class);
    }

    /**
     * Scope: hanya rule berstatus aktif — inilah yang boleh dipakai
     * mesin diagnosis Mahasiswa 2 (M1-FR-009).
     */
    public function scopeAktifSaja(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
