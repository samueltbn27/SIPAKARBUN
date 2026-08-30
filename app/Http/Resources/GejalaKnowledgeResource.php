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
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
        ];
    }
}
