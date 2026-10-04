<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historical request to extend the deadline of a POPT assignment.
 */
class PerpanjanganPenugasan extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'perpanjangan_penugasan';

    protected $fillable = [
        'kasus_id',
        'penugasan_popt_id',
        'requested_by',
        'current_deadline_at',
        'proposed_deadline_at',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'kasus_id' => 'integer',
        'penugasan_popt_id' => 'integer',
        'requested_by' => 'integer',
        'current_deadline_at' => 'datetime',
        'proposed_deadline_at' => 'datetime',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(KasusPenanganan::class, 'kasus_id');
    }

    public function penugasanPopt(): BelongsTo
    {
        return $this->belongsTo(PenugasanPopt::class, 'penugasan_popt_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
