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
use App\Services\LocalKelompokTaniReferensiClient;
use App\Services\LocalKnowledgeApiClient;
use App\Services\LocalKomoditasReferensiClient;
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
        // Runtime selalu membaca normalized local references. HTTP Disbun
        // hanya dipakai oleh command sync; mock sengaja dibatasi ke testing.
        $this->app->bind(KomoditasReferensiClient::class, static function (): KomoditasReferensiClient {
            if (app()->runningUnitTests()) {
                return new \App\Services\MockKomoditasReferensiClient;
            }

            return new LocalKomoditasReferensiClient;
        });

        $this->app->bind(KelompokTaniReferensiClient::class, static function (): KelompokTaniReferensiClient {
            if (app()->runningUnitTests()) {
                return new \App\Services\MockKelompokTaniReferensiClient;
            }

            return new LocalKelompokTaniReferensiClient;
        });

        $this->app->singleton(HttpKomoditasReferensiClient::class, static fn (): HttpKomoditasReferensiClient => new HttpKomoditasReferensiClient(
            baseUrl: (string) config('services.shared_referensi.base_url', ''),
            token: (string) config('services.shared_referensi.token', ''),
            timeout: (int) config('services.shared_referensi.timeout', 30),
            pageSize: (int) config('services.shared_referensi.page_size', 50),
            maxPages: (int) config('services.shared_referensi.max_pages', 250),
            userAgent: (string) config('services.shared_referensi.user_agent', 'SIPAKARBUN/1.0'),
        ));

        $this->app->singleton(HttpKelompokTaniReferensiClient::class, static fn (): HttpKelompokTaniReferensiClient => new HttpKelompokTaniReferensiClient(
            baseUrl: (string) config('services.shared_referensi.base_url', ''),
            token: (string) config('services.shared_referensi.token', ''),
            timeout: (int) config('services.shared_referensi.timeout', 30),
            pageSize: (int) config('services.shared_referensi.page_size', 50),
            maxPages: (int) config('services.shared_referensi.max_pages', 250),
            userAgent: (string) config('services.shared_referensi.user_agent', 'SIPAKARBUN/1.0'),
            pageDelayMs: (int) config('services.shared_referensi.page_delay_ms', 750),
            rateLimitRetries: (int) config('services.shared_referensi.rate_limit_retries', 3),
            rateLimitBackoffMs: (int) config('services.shared_referensi.rate_limit_backoff_ms', 60000),
            sourceExhaustionWarningRatio: (float) config('services.shared_referensi.source_exhaustion_warning_ratio', 0.90),
        ));

        // Knowledge API (Mahasiswa 1) untuk modul Diagnosis (Mahasiswa 2).
        // Base URL, token, dan timeout dibaca dari .env — lihat
        // config/services.php. Pada local monolith tanpa base URL, gunakan
        // adapter yang tetap melewati Knowledge API controller/envelope;
        // production/staging tetap wajib memakai HTTP + Sanctum token.
        $this->app->bind(KnowledgeApiClient::class, static function (): KnowledgeApiClient {
            $baseUrl = (string) config('services.knowledge_api.base_url', '');

            if ($baseUrl === '' && app()->environment('local')) {
                return app(LocalKnowledgeApiClient::class);
            }

            return new HttpKnowledgeApiClient(
                baseUrl: $baseUrl,
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
