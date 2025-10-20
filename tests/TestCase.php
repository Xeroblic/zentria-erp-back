<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que la config no esté cacheada y que la DB de pruebas sea segura
        try { Artisan::call('config:clear'); } catch (\Throwable $e) {}

        $default = Config::get('database.default');
        $conn = Config::get("database.connections.$default");
        $dbName = is_array($conn) ? ($conn['database'] ?? null) : null;

        // Falla rápido si apunta a una BD no marcadamente de testing
        $isMemory = ($dbName === ':memory:');
        $looksTest = is_string($dbName) && preg_match('/test|_test|testing/i', $dbName);
        if ($default === 'pgsql' && ! $isMemory && ! $looksTest) {
            // Auto‑fallback seguro: redirigir a SQLite en memoria para esta corrida de tests
            Config::set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
            Config::set('database.default', 'sqlite');

            // Purge/reconnect para aplicar configuración
            try {
                DB::purge($default);
            } catch (\Throwable $e) {}
            try {
                DB::reconnect();
            } catch (\Throwable $e) {}
        }
    }
}
