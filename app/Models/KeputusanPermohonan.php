<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model KeputusanPermohonan — audit keputusan Operator UPTD atas sebuah
 * permohonan penanganan (kontrak §12).
 *
 * Satu permohonan hanya boleh memiliki SATU keputusan (unique constraint
 * di permohonan_id). `operator_id` adalah user Operator UPTD yang
 * memutuskan, `decided_at` waktu keputusan, `catatan` alasan (wajib saat
 * ditolak, opsional saat diterima).
 */
class KeputusanPermohonan extends Model
{
    use HasFactory;

    public const KEPUTUSAN_DITERIMA = 'diterima';

    public const KEPUTUSAN_DITOLAK = 'ditolak';

    protected $table = 'keputusan_permohonan';

    protected $fillable = [
        'permohonan_id',
        'keputusan',
        'catatan',
        'operator_id',
        'decided_at',
    ];

    protected $casts = [
        'permohonan_id' => 'integer',
        'operator_id' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(PermohonanPenanganan::class, 'permohonan_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
