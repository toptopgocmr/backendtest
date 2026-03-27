<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Chargement des routes admin sous le groupe web
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ══════════════════════════════════════════════════════
        //  FIX CRITIQUE — Laravel 11 enregistre route('login')
        //  par défaut. On remplace ça par admin.login pour les
        //  routes admin, null pour les routes API (→ 401 JSON).
        // ══════════════════════════════════════════════════════
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            // Routes API → pas de redirect, juste 401
            return null;
        });

        // Alias du middleware admin personnalisé
        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Sanctum pour les API stateful (mobile)
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
