<?php

namespace App\Services;

use App\Contracts\KomoditasReferensiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementasi ASLI KomoditasReferensiClient — manggil endpoint
 * internal milik tim Integration (BUKAN Disbun langsung).
 *
 * BELUM DIPAKAI (belum di-bind di AppServiceProvider) karena:
 *  1. Endpoint GET /api/referensi/komoditas milik tim Integration
 *     belum tersedia/dijalankan.
 *  2. Mekanisme autentikasi antar-modul belum disepakati final
 *     (sama seperti isu terbuka di kontrak API M1->M2, tahap #7).
 *  3. Base URL & port service Integration belum ditentukan tim.
 *
 * Nilai config di bawah (INTEGRATION_API_BASE_URL, dst.) HARUS diisi
 * di .env begitu detail-nya sudah didapat dari tim Integration.
 */
class HttpKomoditasReferensiClient implements KomoditasReferensiClient
{
    public function __construct(
        private readonly string $baseUrl = '', // isi dari config('services.integration.base_url')
        private readonly string $token = '',   // isi dari config('services.integration.token')
    ) {
    }

    public function all(): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/referensi/komoditas");

            if ($response->failed()) {
                Log::warning('Gagal ambil referensi komoditas dari Integration API', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json('data', []);
        } catch (\Throwable $e) {
            // Kalau service Integration down, JANGAN sampai ini
            // menjatuhkan seluruh proses Kelola Penyakit di modul kita.
            // Kembalikan array kosong, biar validasi komoditas_id di
            // Form Request gagal dengan pesan wajar, bukan error 500.
            Log::error('Exception saat panggil Integration API komoditas', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }
}
