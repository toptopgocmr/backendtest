<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyPricingController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SupportController;

Route::prefix('v1')->group(function () {

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC
    // ─────────────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('send-otp',        [AuthController::class, 'sendOtp']);
        Route::post('verify-otp',      [AuthController::class, 'verifyOtp']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',  [AuthController::class, 'resetPassword']);
    });

    Route::get('properties',              [PropertyController::class, 'index']);
    Route::get('properties/featured',     [PropertyController::class, 'featured']);
    Route::get('properties/{id}',         [PropertyController::class, 'show']);
    Route::get('properties/{id}/reviews', [ReviewController::class, 'propertyReviews']);

    // ── Grille tarifaire — lecture + calcul publics ──────────────────────────
    // GET  /v1/properties/{id}/pricing           → liste des tarifs actifs
    // POST /v1/properties/{id}/pricing/calculate → calcule le total pour durée donnée
    Route::get('properties/{id}/pricing',           [PropertyPricingController::class, 'index']);
    Route::post('properties/{id}/pricing/calculate',[PropertyPricingController::class, 'calculate']);

    // SUPPORT AGENT — Public
    Route::get('support/agent', [SupportController::class, 'agent']);

    // ─────────────────────────────────────────────────────────────────────────
    // PROTECTED
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // ───── PROFILE
        Route::prefix('profile')->group(function () {
            Route::get('/',        [ProfileController::class, 'show']);
            Route::put('/',        [ProfileController::class, 'update']);
            Route::post('avatar',  [ProfileController::class, 'updateAvatar']);
            Route::put('password', [ProfileController::class, 'changePassword']);
        });

        // ───── PROPERTIES
        Route::post('properties',             [PropertyController::class, 'store']);
        Route::put('properties/{id}',         [PropertyController::class, 'update']);
        Route::delete('properties/{id}',      [PropertyController::class, 'destroy']);
        Route::post('properties/{id}/images', [PropertyController::class, 'uploadImages']);

        // ── Grille tarifaire — écriture protégée (owner / admin) ────────────
        // POST   /v1/properties/{id}/pricing          → créer/mettre à jour la grille
        // DELETE /v1/properties/{id}/pricing/{period} → désactiver un tarif
        Route::post('properties/{id}/pricing',              [PropertyPricingController::class, 'store']);
        Route::delete('properties/{id}/pricing/{period}',   [PropertyPricingController::class, 'destroy']);

        // ───── BOOKINGS
        Route::prefix('bookings')->group(function () {
            Route::get('/',              [BookingController::class, 'index']);
            Route::post('/',             [BookingController::class, 'store']);
            Route::get('{ref}',          [BookingController::class, 'show']);
            Route::put('{ref}/cancel',   [BookingController::class, 'cancel']);
            Route::put('{ref}/confirm',  [BookingController::class, 'confirm']);
            Route::get('{booking}/payment/instructions', [PaymentController::class, 'instructions']);
            Route::post('{booking}/payment',             [PaymentController::class, 'store']);
        });

        // ───── PAYMENTS
        Route::prefix('payments')->group(function () {
            Route::post('initiate',             [PaymentController::class, 'initiate']);
            Route::get('my',                    [PaymentController::class, 'myPayments']);
            Route::get('{ref}/status',          [PaymentController::class, 'status']);
            Route::post('{ref}/confirm-manual', [PaymentController::class, 'confirmManual']);
            Route::get('{ref}',                 [PaymentController::class, 'show']);
        });

        // ───── FAVORITES
        Route::get('favorites',        [FavoriteController::class, 'index']);
        Route::post('favorites/{id}',  [FavoriteController::class, 'toggle']);

        // ───── MESSAGES
        Route::get('messages',           [MessageController::class, 'conversations']);
        Route::get('messages/{userId}',  [MessageController::class, 'thread']);
        Route::post('messages',          [MessageController::class, 'send']);
        Route::put('messages/{id}/read', [MessageController::class, 'markRead']);

        // ───── REVIEWS
        Route::post('reviews',     [ReviewController::class, 'store']);
        Route::put('reviews/{id}', [ReviewController::class, 'update']);

        // ───── NOTIFICATIONS
        Route::get('notifications',           [NotificationController::class, 'index']);
        Route::put('notifications/read-all',  [NotificationController::class, 'readAll']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);
    });
});
