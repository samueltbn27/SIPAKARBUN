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

    public const REVIEW_SETUJU = 'setuju';

    public const REVIEW_DITOLAK = 'ditolak';

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
        'diagnosis_id',
        'gejala_existing_ids',
        'operator_review',
        'operator_review_note',
        'operator_reviewed_by',
        'operator_reviewed_at',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'responded_at',
    ];

    protected $casts = [
        'commodity_id' => 'integer',
        'gejala_id' => 'integer',
        'diagnosis_id' => 'integer',
        'gejala_existing_ids' => 'array',
        'operator_reviewed_by' => 'integer',
        'operator_reviewed_at' => 'datetime',
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

    public function operatorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_reviewed_by');
    }

    public function gejala(): BelongsTo
    {
        return $this->belongsTo(Gejala::class, 'gejala_id');
    }

    /**
     * Diagnosis yang dibuat bersamaan dengan laporan ini. Laporan tidak
     * memengaruhi perhitungan CF diagnosis tersebut — relasi ini murni
     * konteks (gejala baru apa yang juga diamati saat diagnosis itu).
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class, 'diagnosis_id');
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

    /**
     * Kajian POPT sudah menjadi draft dan menunggu Review Operator.
     */
    public function butuhReviewOperator(): bool
    {
        return $this->status === self::STATUS_DRAFT_DIBUAT
            && $this->operator_review === null;
    }

    /**
     * Gate relasi CF: hanya laporan yang disetujui Operator yang boleh
     * dilanjutkan ke penentuan relasi penyakit & CF.
     */
    public function lolosReviewOperator(): bool
    {
        return $this->status === self::STATUS_DRAFT_DIBUAT
            && $this->operator_review === self::REVIEW_SETUJU;
    }

    public function operatorReviewLabel(): ?string
    {
        return match ($this->operator_review) {
            self::REVIEW_SETUJU => 'Disetujui Operator',
            self::REVIEW_DITOLAK => 'Ditolak Operator',
            default => null,
        };
    }

    public function imageUrl(): ?string
    {
        return $this->image_path === null
            ? null
            : route('laporan-gejala.image', $this, false);
    }
}
