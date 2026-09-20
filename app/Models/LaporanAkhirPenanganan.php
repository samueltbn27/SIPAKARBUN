<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Final handling report. Once submitted, the record is immutable.
 */
class LaporanAkhirPenanganan extends Model
{
    use HasFactory;

    protected $table = 'laporan_akhir_penanganan';

    protected $fillable = [
        'kasus_id',
        'penugasan_popt_id',
        'submitted_by',
        'ringkasan_tindakan',
        'hasil_penanganan',
        'rekomendasi',
        'catatan_tambahan',
        'submitted_at',
    ];

    protected $casts = [
        'kasus_id' => 'integer',
        'penugasan_popt_id' => 'integer',
        'submitted_by' => 'integer',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $report): void {
            if ($report->exists && $report->getOriginal('submitted_at') !== null) {
                throw new LogicException('Laporan akhir yang sudah dikirim tidak dapat diubah.');
            }
        });

        static::deleting(function (self $report): void {
            if ($report->submitted_at !== null) {
                throw new LogicException('Laporan akhir yang sudah dikirim tidak dapat dihapus.');
            }
        });
    }

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(KasusPenanganan::class, 'kasus_id');
    }

    public function penugasanPopt(): BelongsTo
    {
        return $this->belongsTo(PenugasanPopt::class, 'penugasan_popt_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(LaporanAkhirEvidence::class, 'laporan_akhir_id')
            ->orderBy('id');
    }
}
