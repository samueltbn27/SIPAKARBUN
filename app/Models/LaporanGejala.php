<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanGejala extends Model
{
    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_PERLU_INFORMASI = 'perlu_informasi';

    public const STATUS_DUPLIKAT = 'duplikat';

    public const STATUS_DRAFT_DIBUAT = 'draft_dibuat';

    protected $table = 'laporan_gejala';

    protected $fillable = [
        'report_code',
        'commodity_id',
        'commodity_name_snapshot',
        'description',
        'location_description',
        'image_path',
        'status',
        'additional_information',
        'review_note',
        'gejala_id',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'responded_at',
    ];

    protected $casts = [
        'commodity_id' => 'integer',
        'gejala_id' => 'integer',
        'created_by' => 'integer',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function gejala(): BelongsTo
    {
        return $this->belongsTo(Gejala::class, 'gejala_id');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PERLU_INFORMASI => 'Perlu informasi tambahan',
            self::STATUS_DUPLIKAT => 'Duplikat',
            self::STATUS_DRAFT_DIBUAT => 'Draft gejala dibuat',
            default => 'Menunggu tinjauan POPT',
        };
    }

    public function imageUrl(): ?string
    {
        return $this->image_path === null
            ? null
            : route('laporan-gejala.image', $this, false);
    }
}
