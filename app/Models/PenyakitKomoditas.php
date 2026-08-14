<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PenyakitKomoditas — relasi antara Penyakit dan komoditas.
 *
 * `komoditas_id` menunjuk ke ref_komoditas.id, tabel SHARED milik tim
 * Integration (bukan Mahasiswa 1). Karena itu TIDAK ada relasi
 * Eloquent belongsTo() ke model komoditas di sini — model itu di luar
 * batas kepemilikan modul ini (§23.1 PRD).
 *
 * Kalau butuh detail komoditas (nama, kode, dst.) saat menampilkan
 * data ini, ambil lewat service/model shared yang disediakan tim
 * Integration, jangan query tabel ref_komoditas langsung dari sini.
 */
class PenyakitKomoditas extends Model
{
    use HasFactory;

    protected $table = 'penyakit_komoditas';

    public $timestamps = false;

    protected $fillable = [
        'penyakit_id',
        'komoditas_id',
    ];

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class);
    }
}
