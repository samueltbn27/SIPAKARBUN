<?php

namespace App\Http\Resources;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'url' => PublicStorageUrl::make($this->file_path, $request),
        ];
    }
}
