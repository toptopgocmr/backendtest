<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\SupportController;

Route::prefix('admin')->name('admin.')->group(function () {

    // ── Non connecté ──────────────────────────────────────────
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
    });

    // ── Connecté (FIX #11 — utilise auth.admin pas auth:admin) ──
    Route::middleware('auth.admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/',           [DashboardController::class, 'index'])->name('dashboard');

        // Propriétés
        Route::get('properties',              [PropertyController::class, 'index'])->name('properties.index');
        Route::get('properties/{id}',         [PropertyController::class, 'show'])->name('properties.show');
        Route::put('properties/{id}/approve', [PropertyController::class, 'approve'])->name('properties.approve');
        Route::delete('properties/{id}',      [PropertyController::class, 'destroy'])->name('properties.destroy');

        // Réservations
        Route::get('bookings',                [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{ref}',          [BookingController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{ref}/confirm',  [BookingController::class, 'confirm'])->name('bookings.confirm');
        Route::put('bookings/{ref}/complete', [BookingController::class, 'complete'])->name('bookings.complete');

        // Paiements
        Route::get('payments',                [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/{ref}/refund',  [PaymentController::class, 'refund'])->name('payments.refund');

        // Utilisateurs
        Route::get('users',              [UserController::class, 'index'])->name('users.index');
        Route::get('users/{id}',         [UserController::class, 'show'])->name('users.show');
        Route::put('users/{id}/toggle',  [UserController::class, 'toggle'])->name('users.toggle');

        // Avis
        Route::get('reviews',               [ReviewController::class, 'index'])->name('reviews.index');
        Route::delete('reviews/{id}',       [ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::put('reviews/{id}/toggle',   [ReviewController::class, 'toggle'])->name('reviews.toggle');

        // Comptabilité
        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');

        // Support
        Route::get('support',              [SupportController::class, 'index'])->name('support.index');
        Route::get('support/{id}',         [SupportController::class, 'show'])->name('support.show');
        Route::post('support/{id}/reply',  [SupportController::class, 'reply'])->name('support.reply');
        Route::put('support/{id}/close',   [SupportController::class, 'close'])->name('support.close');

        // Paramètres
        Route::get('settings', fn() => view('admin.settings'))->name('settings');
    });
});
