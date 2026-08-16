<?php

namespace App\Services;

use App\Contracts\KomoditasReferensiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Implementasi HTTP KomoditasReferensiClient — memanggil endpoint
 * internal milik tim Shared Integration (BUKAN Disbun langsung):
 *
 *   GET {base}/api/referensi/komoditas
 *
 * Konfigurasi (base URL, token, timeout) dibaca dari .env lewat
 * config('services.shared_referensi.*') — lihat binding di
 * AppServiceProvider. Tidak ada nilai yang di-hardcode.
 *
 * Error handling: bila service Integration down / response berubah
 * struktur, client mengembalikan array kosong / null dan mencatat log,
 * agar validasi komoditas_id di Form Request gagal dengan pesan wajar,
 * bukan error 500 mentah.
 */
class HttpKomoditasReferensiClient implements KomoditasReferensiClient
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $token = '',
        private readonly int $timeout = 5,
    ) {}

    public function all(): array
    {
        if ($this->baseUrl === '') {
            Log::warning('SHARED_API_BASE_URL belum diisi — referensi komoditas kosong.');

            return [];
        }

        try {
            $request = Http::acceptJson()
                ->timeout($this->timeout);

            if ($this->token !== '') {
                $request->withToken($this->token);
            }

            $response = $request->get("{$this->baseUrl}/api/referensi/komoditas");

            if ($response->failed()) {
                Log::warning('Gagal ambil referensi komoditas dari Integration API', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json('data');

            if (! is_array($data)) {
                Log::warning('Response komoditas tidak memiliki key "data" berupa array.');

                return [];
            }

            return collect($data)->map(function (mixed $row): array {
                $row = is_array($row) ? $row : [];

                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'kode' => (string) ($row['kode'] ?? ''),
                    'nama' => (string) ($row['nama'] ?? ''),
                    'nama_latin' => $row['nama_latin'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];
            })->values()->all();
        } catch (Throwable $e) {
            // Kalau service Integration down, JANGAN sampai ini
            // menjatuhkan seluruh proses kelola penyakit/permohonan.
            Log::error('Exception saat panggil Integration API komoditas', [
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
