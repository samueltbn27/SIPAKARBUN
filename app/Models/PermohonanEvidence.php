<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PermohonanEvidence — metadata bukti/foto sebuah permohonan.
 *
 * Keamanan: `file_path` menyimpan nama file hasil generate aman (UUID +
 * ekstensi sesuai MIME tervalidasi), bukan nama asli client; `file_name`
 * adalah nama tampilan yang sudah disanitasi.
 *
 * Baris append-only: hanya `created_at`.
 */
class PermohonanEvidence extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'permohonan_evidences';

    protected $fillable = [
        'permohonan_id',
        'file_path',
        'file_name',
        'mime_type',
    ];

    protected $casts = [
        'permohonan_id' => 'integer',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(PermohonanPenanganan::class, 'permohonan_id');
    }
}
