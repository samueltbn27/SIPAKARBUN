<?php

namespace App\Providers;

use App\Contracts\KelompokTaniReferensiClient;
use App\Contracts\KnowledgeApiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Services\CertaintyFactorService;
use App\Services\DiagnosisService;
use App\Services\ForwardChainingService;
use App\Services\HttpKelompokTaniReferensiClient;
use App\Services\HttpKnowledgeApiClient;
use App\Services\HttpKomoditasReferensiClient;
use App\Services\KnowledgeService;
use App\Services\MockKelompokTaniReferensiClient;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // PRD §23.4: komoditas & kelompok tani adalah domain Shared
        // Integration. Saat `SHARED_API_BASE_URL` belum diisi di .env,
        // otomatis memakai MOCK; begitu endpoint Integration tersedia,
        // cukup isi env tersebut dan client bertukar ke HTTP — tanpa
        // mengubah kode lain karena semuanya bergantung ke interface.
        $this->app->bind(KomoditasReferensiClient::class, static function (): KomoditasReferensiClient {
            $baseUrl = (string) config('services.shared_referensi.base_url', '');

            if ($baseUrl === '') {
                return new MockKomoditasReferensiClient;
            }

            return new HttpKomoditasReferensiClient(
                baseUrl: $baseUrl,
                token: (string) config('services.shared_referensi.token', ''),
                timeout: (int) config('services.shared_referensi.timeout', 5),
            );
        });

        $this->app->bind(KelompokTaniReferensiClient::class, static function (): KelompokTaniReferensiClient {
            $baseUrl = (string) config('services.shared_referensi.base_url', '');

            if ($baseUrl === '') {
                return new MockKelompokTaniReferensiClient;
            }

            return new HttpKelompokTaniReferensiClient(
                baseUrl: $baseUrl,
                token: (string) config('services.shared_referensi.token', ''),
                timeout: (int) config('services.shared_referensi.timeout', 5),
            );
        });

        // Knowledge API (Mahasiswa 1) untuk modul Diagnosis (Mahasiswa 2).
        // Base URL, token, dan timeout dibaca dari .env — lihat
        // config/services.php. Tidak ada nilai yang di-hardcode.
        $this->app->bind(KnowledgeApiClient::class, static function (): HttpKnowledgeApiClient {
            return new HttpKnowledgeApiClient(
                baseUrl: (string) config('services.knowledge_api.base_url', ''),
                token: (string) config('services.knowledge_api.token', ''),
                timeout: (int) config('services.knowledge_api.timeout', 5),
            );
        });

        // Layer service diagnosis (tahap #4). KnowledgeService butuh
        // KnowledgeApiClient yang dibind di atas; tiga service lain
        // resolvable otomatis via constructor injection, binding eksplisit
        // di sini hanya untuk kejelasan & konsistensi arsitektur.
        $this->app->bind(KnowledgeService::class, static function ($app): KnowledgeService {
            return new KnowledgeService(
                $app->make(KnowledgeApiClient::class)
            );
        });

        $this->app->bind(ForwardChainingService::class);

        $this->app->bind(CertaintyFactorService::class);

        $this->app->bind(DiagnosisService::class, static function ($app): DiagnosisService {
            return new DiagnosisService(
                $app->make(KnowledgeService::class),
                $app->make(ForwardChainingService::class),
                $app->make(CertaintyFactorService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiter khusus modul Diagnosis (M2). Diagnosis bikin beban
        // (panggil Knowledge API + hitung + simpan), jadi dibatasi per
        // user per menit supaya tidak dipakai untuk spam/DoS.
        RateLimiter::for('diagnosis', static function (Request $request): Limit {
            $userId = $request->user()?->id;
            $key = $userId !== null ? (string) $userId : (string) $request->ip();

            return Limit::perMinute((int) config('services.diagnosis.rate_limit_per_minute', 20))
                ->by('diagnosis:'.$key);
        });

        // Rate limiter unggah permohonan (M2). Batasi frekuensi pembuatan
        // permohonan per user supaya tidak dipakai untuk membanjiri storage.
        RateLimiter::for('permohonan', static function (Request $request): Limit {
            $userId = $request->user()?->id;
            $key = $userId !== null ? (string) $userId : (string) $request->ip();

            return Limit::perMinute((int) config('services.permohonan.rate_limit_per_minute', 10))
                ->by('permohonan:'.$key);
        });
    }
}
