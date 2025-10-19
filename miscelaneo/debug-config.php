<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG DE CONFIGURACIÓN ===" . PHP_EOL;
echo "FALABELLA_USE_MOCK (env): '" . env('FALABELLA_USE_MOCK') . "' (tipo: " . gettype(env('FALABELLA_USE_MOCK')) . ")" . PHP_EOL;
echo "Config falabella.use_mock: " . (config('falabella.use_mock') ? 'true' : 'false') . " (tipo: " . gettype(config('falabella.use_mock')) . ")" . PHP_EOL;
echo PHP_EOL;

echo "=== PARSEO MANUAL ===" . PHP_EOL;
$envValue = env('FALABELLA_USE_MOCK');
echo "Valor raw: '{$envValue}'" . PHP_EOL;
echo "filter_var resultado: " . (filter_var($envValue, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false') . PHP_EOL;
echo PHP_EOL;

echo "=== TODA LA CONFIG DE FALABELLA ===" . PHP_EOL;
var_dump(config('falabella'));
