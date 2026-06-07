<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    | FIX Railway : 'cookie' cause des pertes de session sur Railway (proxy).
    | Utiliser 'database' (stable) ou 'file' (simple).
    | Définir SESSION_DRIVER=database dans les variables Railway.
    */
    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    'encrypt' => false,

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => 'sessions',

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    | FIX Railway : pas de wildcard domain — laisser null pour que Laravel
    | utilise automatiquement le domaine courant (tholadimmo.up.railway.app).
    | Ne PAS mettre '.railway.app' car ça partage la session entre sous-domaines.
    */
    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    | Railway est toujours HTTPS → true est correct.
    */
    'secure' => env('SESSION_SECURE_COOKIE', true),

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    | 'lax' est correct pour les formulaires POST same-origin.
    */
    'same_site' => 'lax',

    'partitioned' => false,

];
