<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
| Projet : ImmoStay — Tholad Group
| Compatible Railway / Production
*/

// ROOT / HEALTH CHECK (Railway utilise "/")
Route::get('/', function () {
    return response()->json([
        'status'  => 'ok',
        'app'     => 'ImmoStay — Tholad Group',
        'version' => '1.0.0',
        'time'    => now()->toDateTimeString(),
    ]);
});

// HEALTH CHECK (monitoring externe)
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'service'   => 'running',
        'timestamp' => now()->timestamp,
    ]);
});

// TEST STORAGE — vérifie que l'upload d'images fonctionne (paiement manuel)
Route::get('/test-storage', function () {
    return response()->json([
        'storage_link_exists' => file_exists(public_path('storage')),
        'example_url'         => asset('storage/payments/test.jpg'),
    ]);
});

// ⚠️  Route::get('/admin', ...) SUPPRIMÉE volontairement.
//     Le panel admin est géré intégralement par routes/admin.php
//     (middleware auth:admin). La route JSON ci-dessous court-circuitait
//     le routage Blade et empêchait l'accès au panneau.

// FALLBACK 404
Route::fallback(function () {
    return response()->json([
        'status'  => 'error',
        'message' => 'Route not found',
        'code'    => 404,
    ], 404);
});
