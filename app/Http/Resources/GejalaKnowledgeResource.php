<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Resource untuk kontrak API M1 -> M2 (GET /api/gejala).
 *
 * Bentuk JSON ini STABIL dan jadi kontrak resmi ke Mahasiswa 2.
 * Perubahan field apa pun WAJIB dikomunikasikan ke M2 (§23.1 PRD).
 */
class GejalaKnowledgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'image_path' => $this->image_path,
            'image_url' => $this->imageUrl($request),
        ];
    }

    private function imageUrl(Request $request): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $storageUrl = Storage::disk('public')->url($this->image_path);
        $storagePath = parse_url($storageUrl, PHP_URL_PATH);

        if (! is_string($storagePath) || $storagePath === '') {
            return $storageUrl;
        }

        // The public disk is local storage. Rebuild its URL from the current
        // request origin so APP_URL=localhost cannot break a 127.0.0.1:8000
        // browser session (or another local development port).
        return rtrim($request->getSchemeAndHttpHost(), '/').'/'.ltrim($storagePath, '/');
    }
}
