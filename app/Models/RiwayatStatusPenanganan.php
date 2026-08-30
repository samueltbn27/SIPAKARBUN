<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model RiwayatStatusPenanganan — audit trail APPEND-ONLY status kasus.
 *
 * Kontrak §17: JANGAN menimpa status lama. SETIAP transisi membuat baris
 * baru; status aktif tersimpan cepat di kasus_penanganan.current_status.
 * `previous_status` nullable untuk status awal (kasus lahir = 'diterima').
 *
 * `created_at` sengaja ditulis eksplisit oleh service (tabel tidak
 * auto-timestamp; setiap transisi dicatat tepat saat terjadi).
 */
class RiwayatStatusPenanganan extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'riwayat_status_penanganan';

    protected $fillable = [
        'kasus_id',
        'previous_status',
        'status',
        'catatan',
        'actor_id',
        'created_at',
    ];

    protected $casts = [
        'kasus_id' => 'integer',
        'actor_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(KasusPenanganan::class, 'kasus_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
