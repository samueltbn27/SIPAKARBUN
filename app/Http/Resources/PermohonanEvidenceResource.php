<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * PermohonanEvidenceResource — metadata file bukti/foto permohonan.
 *
 * Hanya mengekspos NAMA tampilan yang sudah disanitasi (tidak pernah
 * nama asli client / path mentah), konsisten dengan EvidenceFileHandler.
 */
class PermohonanEvidenceResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'evidence_id' => $this->id,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'url' => Storage::disk('public')->url($this->file_path),
        ];
    }
}
