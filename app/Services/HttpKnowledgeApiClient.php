<?php

namespace App\Services;

use App\Contracts\KnowledgeApiClient;
use App\Exceptions\KnowledgeApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Konsumen Knowledge API milik Mahasiswa 1 (GET /api/penyakit dan
 * GET /api/gejala). Bukan akses DB langsung — sesuai batas kepemilikan
 * modul (§23.1 PRD), Mahasiswa 2 hanya boleh membaca knowledge lewat
 * API yang disediakan Mahasiswa 1.
 *
 * Konfigurasi (base URL, token, timeout) dibaca dari .env lewat
 * config('services.knowledge_api.*') — lihat binding di
 * AppServiceProvider. Tidak ada token yang di-hardcode di sini.
 *
 * Error handling: semua kegagalan (HTTP non-2xx, gagal koneksi,
 * struktur response berubah) diubah menjadi KnowledgeApiException
 * supaya pemanggil (business logic diagnosis) menangkapnya dengan
 * spesifik — bukan mengembalikan array kosong yang berisiko dianggap
 * "penyakit tidak ditemukan" oleh mesin diagnosis.
 */
class HttpKnowledgeApiClient implements KnowledgeApiClient
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $token = '',
        private readonly int $timeout = 5,
    ) {}

    public function penyakit(?int $komoditasId = null): Collection
    {
        $payload = $this->get('/api/penyakit', $komoditasId);

        return collect($payload['data'])->map(function (mixed $item): array {
            if (! is_array($item) || ! array_key_exists('id', $item) || ! array_key_exists('nama', $item)) {
                throw KnowledgeApiException::invalidResponse(
                    'item penyakit tidak memiliki id/nama.'
                );
            }

            return [
                'id' => (int) $item['id'],
                'kode' => $item['kode'] ?? null,
                'nama' => (string) $item['nama'],
                'deskripsi' => $item['deskripsi'] ?? null,
                'komoditas_id' => $this->intList($item['komoditas_id'] ?? []),
                'aturan_cf' => $this->normalizeAturanCf($item['aturan_cf'] ?? []),
                'solusi' => $this->normalizeSolusi($item['solusi'] ?? []),
                'updated_at' => $item['updated_at'] ?? null,
            ];
        })->values();
    }

    public function gejala(?int $komoditasId = null): Collection
    {
        $payload = $this->get('/api/gejala', $komoditasId);

        return collect($payload['data'])->map(function (mixed $item): array {
            if (! is_array($item) || ! array_key_exists('id', $item) || ! array_key_exists('nama', $item)) {
                throw KnowledgeApiException::invalidResponse(
                    'item gejala tidak memiliki id/nama.'
                );
            }

            return [
                'id' => (int) $item['id'],
                'kode' => $item['kode'] ?? null,
                'nama' => (string) $item['nama'],
                'deskripsi' => $item['deskripsi'] ?? null,
            ];
        })->values();
    }

    /**
     * Eksekusi GET + validasi awal response: status 2xx dan payload
     * harus punya key "data" berupa array.
     */
    private function get(string $path, ?int $komoditasId): array
    {
        if ($this->baseUrl === '') {
            throw KnowledgeApiException::configurationError(
                'KNOWLEDGE_API_BASE_URL belum diisi di .env.'
            );
        }

        if ($this->token === '') {
            throw KnowledgeApiException::configurationError(
                'KNOWLEDGE_API_TOKEN belum diisi di .env.'
            );
        }

        try {
            $request = Http::acceptJson()
                ->withToken($this->token)
                ->timeout($this->timeout);

            $response = $komoditasId === null
                ? $request->get($this->baseUrl.$path)
                : $request->get($this->baseUrl.$path, ['komoditas_id' => $komoditasId]);

            if ($response->failed()) {
                throw KnowledgeApiException::serverError(
                    $response->status(),
                    $response->body()
                );
            }

            $payload = $response->json();

            if (! is_array($payload) || ! array_key_exists('data', $payload) || ! is_array($payload['data'])) {
                throw KnowledgeApiException::invalidResponse(
                    'payload tidak memiliki key "data" berupa array.'
                );
            }

            return $payload;
        } catch (KnowledgeApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw KnowledgeApiException::networkError($e);
        }
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

        return collect($solusi)->map(function (mixed $item): array {
            $item = is_array($item) ? $item : [];

            return [
                'judul' => $item['judul'] ?? null,
                'deskripsi' => $item['deskripsi'] ?? null,
            ];
        })->values()->all();
    }

    private function intList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_map('intval', $values);
    }
}
