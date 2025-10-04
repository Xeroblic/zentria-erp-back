<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Falabella\FalabellaClient;
use App\Services\Falabella\FalabellaApiService;
use App\Services\Falabella\FalabellaMockService;

/**
 * Service Provider para el servicio de Falabella
 * Registra el cliente apropiado (mock o real) según la configuración
 */
class FalabellaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $useMock = config('falabella.use_mock'); // ya es booleano real por el config
        $this->app->singleton(\App\Services\Falabella\FalabellaClient::class, function () use ($useMock) {
            return $useMock
                ? new \App\Services\Falabella\FalabellaMockService()
                : new \App\Services\Falabella\FalabellaApiService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publicar configuración si es necesario
        $this->publishes([
            __DIR__.'/../../config/falabella.php' => config_path('falabella.php'),
        ], 'falabella-config');
    }
}
