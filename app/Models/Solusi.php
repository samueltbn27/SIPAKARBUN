<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Solusi — rekomendasi penanganan untuk satu Penyakit tertentu.
 * Relasi 1-N langsung (bukan many-to-many) sesuai §22.4 PRD.
 */
class Solusi extends Model
{
    use HasFactory;

    protected $table = 'solusi';

    protected $fillable = [
        'penyakit_id',
        'judul',
        'deskripsi',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class);
    }

    /**
     * Scope: hanya solusi berstatus aktif/published.
     */
    public function scopeAktifSaja(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }
}
