<?php

use Illuminate\Support\Facades\Route;

// FIX #2 — Suppression des controllers inexistants.
// Le projet est une API + panel admin uniquement.
// Les routes web utilisateurs n'ont pas de controllers — on redirige vers l'admin.

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Route de santé publique
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'app' => 'ImmoStay — Tholad Group']);
});
