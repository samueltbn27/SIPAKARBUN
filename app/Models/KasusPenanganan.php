<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model KasusPenanganan — entitas kasus yang lahir ketika permohonan
 * DITERIMA oleh Operator UPTD (kontrak §12-13).
 *
 * Karakteristik komoditas & penyakit di-snapshot saat kasus dibuat agar
 * API untuk Mahasiswa 3 stabil walau referensi berubah sesudahnya.
 *
 * `current_status` adalah status AKTIF untuk query cepat; SETIAP transisi
 * tercatat append-only di `riwayat_status_penanganan`. Status awal setelah
 * permohonan diterima = `diterima` (menunggu penugasan POPT), lalu menjadi
 * `ditugaskan` setelah POPT di-assign (fase berikutnya).
 */
class KasusPenanganan extends Model
{
    use HasFactory;

    public const STATUS_DITERIMA = 'diterima';

    public const STATUS_DITUGASKAN = 'ditugaskan';

    public const STATUS_SEDANG_DIREVIEW = 'sedang_direview';

    public const STATUS_DITUNDA = 'ditunda';

    public const STATUS_SIAP_DIEKSEKUSI = 'siap_dieksekusi';

    public const STATUS_DALAM_PELAKSANAAN = 'dalam_pelaksanaan';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'kasus_penanganan';

    protected $fillable = [
        'permohonan_id',
        'kasus_code',
        'current_status',
        'komoditas_id',
        'komoditas_code_snapshot',
        'komoditas_name_snapshot',
        'penyakit_id',
        'penyakit_kode_snapshot',
        'penyakit_name_snapshot',
        'latitude_kasus',
        'longitude_kasus',
        'created_by',
    ];

    protected $casts = [
        'permohonan_id' => 'integer',
        'komoditas_id' => 'integer',
        'penyakit_id' => 'integer',
        'latitude_kasus' => 'float',
        'longitude_kasus' => 'float',
        'created_by' => 'integer',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(PermohonanPenanganan::class, 'permohonan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function penugasanPopt(): HasMany
    {
        return $this->hasMany(PenugasanPopt::class, 'kasus_id');
    }

    /**
     * Penugasan POPT yang sedang AKTIF (1 kasus = maksimal 1).
     */
    public function penugasanAktif(): HasOne
    {
        return $this->hasOne(PenugasanPopt::class, 'kasus_id')
            ->where('status', PenugasanPopt::STATUS_AKTIF)
            ->latestOfMany();
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPenanganan::class, 'kasus_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
