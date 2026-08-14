<?php

namespace App\Providers;

use App\Contracts\KomoditasReferensiClient;
use App\Services\HttpKomoditasReferensiClient;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
