<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route; // WICHTIG: Import für Route::middleware

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // NEU: Lädt die Standard-API-Routen
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));

        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.cfx' => \App\Http\Middleware\EnsureAuthenticatedViaCfx::class,
        ]);
    })
    ->withProviders([
        App\Providers\AuthServiceProvider::class,
        Lab404\Impersonate\ImpersonateServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();