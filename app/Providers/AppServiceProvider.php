<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fix compatibilité MySQL anciennes versions (utf8mb4 index limit)
        Schema::defaultStringLength(191);

        // FIX HTTPS Railway : forcer HTTPS pour toutes les URLs générées
        // Railway est derrière un reverse proxy — Laravel doit forcer le schéma HTTPS
        // pour éviter les redirections 307 et les erreurs "Mixed content"
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
