<?php

namespace App\Providers;

use App\Contracts\KnowledgeApiClient;
use App\Contracts\KomoditasReferensiClient;
use App\Services\CertaintyFactorService;
use App\Services\DiagnosisService;
use App\Services\ForwardChainingService;
use App\Services\HttpKnowledgeApiClient;
use App\Services\KnowledgeService;
use App\Services\MockKomoditasReferensiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // PRD §23.4: komoditas adalah domain Shared Integration.
        // Saat ini pakai MOCK karena endpoint asli belum siap.
        // Untuk beralih ke HTTP asli, ganti Mock -> Http di sini saja.
        $this->app->bind(
            KomoditasReferensiClient::class,
            MockKomoditasReferensiClient::class
        );

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
        //
    }
}
