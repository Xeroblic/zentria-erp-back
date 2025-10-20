<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Si por alguna razón el proceso web queda con APP_ENV=testing sin estar ejecutando PHPUnit,
        // forzamos a usar la conexión de BD real para evitar apuntar a sqlite :memory:
        if (app()->environment('testing') && ! app()->runningUnitTests() && ! env('PHPUNIT_RUNNING', false)) {
            $fallback = env('DB_CONNECTION_REAL', 'pgsql');
            Config::set('database.default', $fallback);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
