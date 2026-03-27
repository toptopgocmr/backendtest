<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Laravel 11 — Ce middleware est conservé mais la redirection
 * des invités est désormais configurée dans bootstrap/app.php
 * via $middleware->redirectGuestsTo().
 *
 * Si redirectGuestsTo n'est pas défini, ce fallback s'applique.
 */
class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        return null;
    }
}
