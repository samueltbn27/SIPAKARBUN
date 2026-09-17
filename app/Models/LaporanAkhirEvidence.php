<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only photo metadata for a final handling report.
 */
class LaporanAkhirEvidence extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'laporan_akhir_evidences';

    protected $fillable = [
        'laporan_akhir_id',
        'file_path',
        'file_name',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'laporan_akhir_id' => 'integer',
        'uploaded_by' => 'integer',
    ];

    public function laporanAkhir(): BelongsTo
    {
        return $this->belongsTo(LaporanAkhirPenanganan::class, 'laporan_akhir_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
