<?php

namespace App\Services;

use App\Contracts\KelompokTaniReferensiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Implementasi HTTP KelompokTaniReferensiClient — memanggil endpoint
 * internal milik tim Shared Integration:
 *
 *   GET {base}/api/referensi/kelompok-tani
 *
 * Konfigurasi (base URL, token, timeout) dibaca dari .env lewat
 * config('services.shared_referensi.*') — lihat binding di
 * AppServiceProvider. Tidak ada nilai yang di-hardcode.
 *
 * Error handling: bila service Integration down / response berubah
 * struktur, client TIDAK melempar pengecualian (agar tidak menjatuhkan
 * permohonan) — ia mengembalikan array kosong / null dan mencatat log.
 * Pemanggil (validasi FormRequest / resource) menangkap kondisi kosong
 * sebagai "data belum tersedia", bukan crash 500.
 */
class HttpKelompokTaniReferensiClient implements KelompokTaniReferensiClient
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $token = '',
        private readonly int $timeout = 5,
    ) {}

    public function all(): array
    {
        if ($this->baseUrl === '') {
            Log::warning('SHARED_API_BASE_URL belum diisi — referensi kelompok tani kosong.');

            return [];
        }

        try {
            $request = Http::acceptJson()
                ->timeout($this->timeout);

            if ($this->token !== '') {
                $request->withToken($this->token);
            }

            $response = $request->get("{$this->baseUrl}/api/referensi/kelompok-tani");

            if ($response->failed()) {
                Log::warning('Gagal ambil referensi kelompok tani dari Integration API', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json('data');

            if (! is_array($data)) {
                Log::warning('Response kelompok tani tidak memiliki key "data" berupa array.');

                return [];
            }

            return collect($data)->map(function (mixed $row): array {
                $row = is_array($row) ? $row : [];

                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'kode' => (string) ($row['kode'] ?? ''),
                    'nama' => (string) ($row['nama'] ?? ''),
                    'ketua' => $row['ketua'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];
            })->values()->all();
        } catch (Throwable $e) {
            Log::error('Exception saat panggil Integration API kelompok tani', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }
}
