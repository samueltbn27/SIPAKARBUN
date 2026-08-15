<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Diagnosis — transaksi diagnosis yang dijalankan user (M2).
 *
 * Menyimpan konteks transaksi: siapa (user_id), komoditas yang dipilih,
 * dan status. Detail gejala yang dipilih dan hasil CF tersimpan di relasi
 * `symptoms` dan `results`.
 *
 * `user_id` menunjuk ke users.id (shared/auth). Relasi Eloquent ke User
 * dibuat di sini karena tabel `users` ada di monolith ini; jika user
 * dihapus, kolom user_id otomatis menjadi null (nullOnDelete), bukan
 * menghapus riwayat diagnosis.
 *
 * `commodity_id` menunjuk ke ref_komoditas.id milik Shared Integration
 * (bukan domain modul ini) — karena itu TIDAK ada relasi Eloquent ke model
 * komoditas, konsisten dengan PenyakitKomoditas (§23.1 PRD).
 */
class Diagnosis extends Model
{
    use HasFactory;

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'diagnoses';

    protected $fillable = [
        'user_id',
        'commodity_id',
        'status',
    ];

    protected $casts = [
        'commodity_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function symptoms(): HasMany
    {
        return $this->hasMany(DiagnosisSymptom::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(DiagnosisResult::class)
            ->orderBy('ranking');
    }

    /**
     * Scope: transaksi diagnosis milik user tertentu.
     */
    public function scopeUntukUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
