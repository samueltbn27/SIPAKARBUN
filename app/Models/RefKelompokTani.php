<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefKelompokTani extends Model
{
    use HasFactory;
    protected $table = 'ref_kelompok_tani';
    public const SOURCE_DISBUN = 'disbun';
    public const SYNC_SYNCED = 'synced';
    public const SYNC_QUARANTINED = 'quarantined';
    protected $fillable = [
        'disbun_record_id', 'source', 'kode', 'kode_kelompok', 'nama', 'ketua', 'kabupaten', 'kecamatan', 'desa',
        'kelurahan', 'kode_kabupaten', 'kode_kecamatan', 'kode_desa', 'latitude', 'longitude', 'status', 'deleted_at',
        'jenis_komoditi', 'external_commodity_id', 'external_commodity_code', 'external_commodity_name', 'commodity_ref_id',
        'commodity_mapping_status', 'source_is_active', 'is_verified', 'sync_status', 'quarantine_reason',
        'source_updated_at', 'last_synced_at',
    ];
    protected $casts = [
        'source_is_active' => 'boolean', 'is_verified' => 'boolean', 'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime', 'commodity_ref_id' => 'integer', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
        'deleted_at' => 'datetime',
    ];
    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_DISBUN)->where('source_is_active', true)
            ->whereNull('deleted_at')->where('is_verified', true)->where('sync_status', '!=', self::SYNC_QUARANTINED);
    }
}
