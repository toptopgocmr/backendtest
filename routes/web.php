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

// PRIVACY POLICY — requis par Google Play (policy violation fix)
// URL déclarée dans Play Console > App content > Privacy policy
Route::get('/privacy', function () {
    return view('privacy');
});

// ⚠️  Route::get('/admin', ...) SUPPRIMÉE volontairement.
//     Le panel admin est géré intégralement par routes/admin.php
//     (middleware auth:admin). La route JSON ci-dessous court-circuitait
//     le routage Blade et empêchait l'accès au panneau.


// STORAGE PROXY — sert les fichiers uploadés sans lien symbolique
// Railway ne supporte pas php artisan storage:link (filesystem éphémère)
// Cette route lit le fichier depuis storage/app/public/ et le retourne
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'Fichier introuvable');
    }

    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    return response()->file($fullPath, [
        'Content-Type'  => $mimeType,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

// FALLBACK 404
Route::fallback(function () {
    return response()->json([
        'status'  => 'error',
        'message' => 'Route not found',
        'code'    => 404,
    ], 404);
});
