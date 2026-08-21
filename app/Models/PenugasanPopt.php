<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PenugasanPopt — penugasan seorang POPT ke sebuah kasus.
 *
 * Kontrak §13: role POPT tunggal (`popt`) yang valid dipilih Operator UPTD
 * hanyalah user ber-role popt DAN is_active = true (dijaga di layer
 * service). 1 kasus = 1 POPT AKTIF pada satu waktu (MVP): penugasan baru
 * menutup penugasan aktif lama menjadi `dicabut` (ditarik kembali), riwayat
 * penugasan tidak pernah di-overwrite.
 */
class PenugasanPopt extends Model
{
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DICABUT = 'dicabut';

    protected $table = 'penugasan_popt';

    protected $fillable = [
        'kasus_id',
        'popt_id',
        'assigned_by',
        'status',
        'catatan',
        'assigned_at',
    ];

    protected $casts = [
        'kasus_id' => 'integer',
        'popt_id' => 'integer',
        'assigned_by' => 'integer',
        'assigned_at' => 'datetime',
    ];

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(KasusPenanganan::class, 'kasus_id');
    }

    public function popt(): BelongsTo
    {
        return $this->belongsTo(User::class, 'popt_id');
    }

    public function assignor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
