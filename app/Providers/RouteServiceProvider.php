<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * En Laravel 11, le routage est géré dans bootstrap/app.php.
 * Ce provider est conservé pour compatibilité mais ne fait rien.
 */
class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';
    public const ADMIN_HOME = '/admin';

    public function boot(): void
    {
        // Laravel 11 : routing configuré dans bootstrap/app.php
    }
}
