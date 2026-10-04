<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only progress note for an active handling assignment.
 */
class ProgresPenanganan extends Model
{
    use HasFactory;

    protected $table = 'progres_penanganan';

    protected $fillable = [
        'kasus_id',
        'penugasan_popt_id',
        'actor_id',
        'catatan',
    ];

    protected $casts = [
        'kasus_id' => 'integer',
        'penugasan_popt_id' => 'integer',
        'actor_id' => 'integer',
    ];

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(KasusPenanganan::class, 'kasus_id');
    }

    public function penugasanPopt(): BelongsTo
    {
        return $this->belongsTo(PenugasanPopt::class, 'penugasan_popt_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
