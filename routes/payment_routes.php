<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Paiement Manuel – à ajouter dans routes/api.php
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // ── CLIENT ───────────────────────────────────────────────────────────────
    Route::prefix('bookings/{booking}')->group(function () {
        // Instructions de paiement (numéros, montant)
        Route::get('payment/instructions', [PaymentController::class, 'instructions'])
            ->name('payment.instructions');

        // Soumettre la preuve de paiement
        Route::post('payment', [PaymentController::class, 'store'])
            ->name('payment.store');
    });

    // Voir le statut d'un paiement (polling ou push notification)
    Route::get('payments/{payment}', [PaymentController::class, 'show'])
        ->name('payment.show');

    // Historique des paiements du client connecté
    Route::get('payments', [PaymentController::class, 'index'])
        ->name('payment.index');

    // ── ADMIN ─────────────────────────────────────────────────────────────────
    Route::prefix('admin/payments')->middleware('role:admin')->group(function () {
        // Liste + stats
        Route::get('/', [AdminPaymentController::class, 'index'])
            ->name('admin.payment.index');

        // Détail avec preuve
        Route::get('{payment}', [AdminPaymentController::class, 'show'])
            ->name('admin.payment.show');

        // ✅ Valider
        Route::patch('{payment}/validate', [AdminPaymentController::class, 'validate'])
            ->name('admin.payment.validate');

        // ❌ Rejeter
        Route::patch('{payment}/reject', [AdminPaymentController::class, 'reject'])
            ->name('admin.payment.reject');
    });
});
