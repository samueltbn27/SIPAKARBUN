<?php

namespace App\Services;

use App\Contracts\KnowledgeApiClient;
use App\Exceptions\KnowledgeApiException;
use App\Http\Controllers\KnowledgeApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Local-only adapter for the monolith development environment.
 *
 * It invokes the same Knowledge API controller and normalizes its {data: []}
 * envelope; M2 still depends on the API contract and never queries M1 tables.
 * Production/staging continue using HttpKnowledgeApiClient and a service token.
 */
class LocalKnowledgeApiClient implements KnowledgeApiClient
{
    public function __construct(private readonly KnowledgeApiController $controller) {}

    public function penyakit(?int $komoditasId = null): Collection
    {
        $payload = $this->payload('/api/penyakit', $komoditasId);

        return collect($payload['data'])->map(function (mixed $item): array {
            if (! is_array($item) || ! array_key_exists('id', $item) || ! array_key_exists('nama', $item)) {
                throw KnowledgeApiException::invalidResponse('item penyakit tidak memiliki id/nama.');
            }

            return [
                'id' => (int) $item['id'],
                'kode' => $item['kode'] ?? null,
                'nama' => (string) $item['nama'],
                'deskripsi' => $item['deskripsi'] ?? null,
                'image_path' => $item['image_path'] ?? null,
                'image_url' => $item['image_url'] ?? null,
                'komoditas_id' => $this->intList($item['komoditas_id'] ?? []),
                'aturan_cf' => $this->normalizeAturanCf($item['aturan_cf'] ?? []),
                'solusi' => $this->normalizeSolusi($item['solusi'] ?? []),
                'updated_at' => $item['updated_at'] ?? null,
            ];
        })->values();
    }

    public function gejala(?int $komoditasId = null): Collection
    {
        $payload = $this->payload('/api/gejala', $komoditasId);

        return collect($payload['data'])->map(function (mixed $item): array {
            if (! is_array($item) || ! array_key_exists('id', $item) || ! array_key_exists('nama', $item)) {
                throw KnowledgeApiException::invalidResponse('item gejala tidak memiliki id/nama.');
            }

            return [
                'id' => (int) $item['id'],
                'kode' => $item['kode'] ?? null,
                'nama' => (string) $item['nama'],
                'deskripsi' => $item['deskripsi'] ?? null,
                'image_path' => $item['image_path'] ?? null,
                'image_url' => $item['image_url'] ?? null,
            ];
        })->values();
    }

    private function payload(string $path, ?int $komoditasId): array
    {
        $request = Request::create($path, 'GET', $komoditasId === null ? [] : ['komoditas_id' => $komoditasId]);
        $response = str_ends_with($path, '/penyakit')
            ? $this->controller->penyakit($request)
            : $this->controller->gejala($request);
        $payload = $response->response()->getData(true);

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            throw KnowledgeApiException::invalidResponse('payload tidak memiliki key "data" berupa array.');
        }

        return $payload;
    }

    private function normalizeAturanCf(mixed $aturanCf): array
    {
        if (! is_array($aturanCf)) {
            return [];
        }

        return collect($aturanCf)->map(function (mixed $rule): array {
            $rule = is_array($rule) ? $rule : [];

            return [
                'gejala_id' => (int) ($rule['gejala_id'] ?? 0),
                'gejala_nama' => $rule['gejala_nama'] ?? null,
                'cf_pakar' => (float) ($rule['cf_pakar'] ?? 0.0),
            ];
        })->values()->all();
    }

    private function normalizeSolusi(mixed $solusi): array
    {
        if (! is_array($solusi)) {
            return [];
        }

        return collect($solusi)->map(fn (mixed $item): array => [
            'judul' => is_array($item) ? ($item['judul'] ?? null) : null,
            'deskripsi' => is_array($item) ? ($item['deskripsi'] ?? null) : null,
        ])->values()->all();
    }

    private function intList(mixed $values): array
    {
        return is_array($values) ? array_map('intval', $values) : [];
    }
}
