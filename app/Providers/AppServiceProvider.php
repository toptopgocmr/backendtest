<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // FIX Pagination : le layout admin utilise Tailwind CSS
        // Sans ça, $collection->links() crashe ou affiche du HTML Bootstrap non stylé
        Paginator::useTailwind();
    }
}
