<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Compatibilité MySQL anciennes versions (utf8mb4 index limit)
        Schema::defaultStringLength(191);
    }
}
