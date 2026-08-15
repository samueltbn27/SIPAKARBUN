<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model RefKomoditas — referensi komoditas internal SIPAKARBUN.
 *
 * Sumber kebenaran ID internal (Gate 3 Reference Contract PRD §43):
 * semua modul (M1/M2/M3) memakai ref_komoditas.id pada FK domain,
 * bukan numeric ID endpoint eksternal Disbun (BR-012).
 *
 * Data diisi oleh tim Integration lewat sync dari API Disbun
 * (INT-FR-002); record anomali ditandai sync_status=quarantined dan
 * tidak boleh dipakai knowledge/diagnosis (M1-AC-006, INT-FR-007).
 */
class RefKomoditas extends Model
{
    use HasFactory;

    protected $table = 'ref_komoditas';

    public const SYNC_SYNCED = 'synced';
    public const SYNC_QUARANTINED = 'quarantined';
    public const SYNC_PENDING = 'pending';

    protected $fillable = [
        'disbun_record_id',
        'kode',
        'nama',
        'nama_latin',
        'source_is_active',
        'is_verified',
        'sync_status',
        'source_updated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'source_is_active' => 'boolean',
        'is_verified' => 'boolean',
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function penyakitKomoditas(): HasMany
    {
        return $this->hasMany(PenyakitKomoditas::class, 'komoditas_id');
    }

    /**
     * Scope: komoditas yang layak dipakai knowledge/diagnosis —
     * terverifikasi dan tidak dikarantina (M1-AC-006: data anomali
     * tidak boleh masuk dropdown knowledge).
     */
    public function scopeTersedia(Builder $query): Builder
    {
        return $query
            ->where('is_verified', true)
            ->where('sync_status', '!=', self::SYNC_QUARANTINED);
    }

    public function scopeTerverifikasi(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeQuarantined(Builder $query): Builder
    {
        return $query->where('sync_status', self::SYNC_QUARANTINED);
    }
}
