<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\CreatePermissionGroupsConfig::class,
        \App\Console\Commands\SeedPermissions::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth'                  => \App\Http\Middleware\Authenticate::class,
            'rol'                   => \App\Http\Middleware\CheckRol::class,
            'verificar.activacion'  => \App\Http\Middleware\VerificarActivacion::class,
            'verificar.empresa'     => \App\Http\Middleware\VerificarAccesoEmpresa::class,
            // Autorizaciones
            'can'                   => \Illuminate\Auth\Middleware\Authorize::class,
            'role'                  => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            'permission'            => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            'role_or_permission'    => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
