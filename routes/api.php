<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| ImmoStay API Routes — v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /* ── Public ── */
    Route::prefix('auth')->group(function () {
        Route::post('register',          [AuthController::class, 'register']);
        Route::post('login',             [AuthController::class, 'login']);
        Route::post('send-otp',          [AuthController::class, 'sendOtp']);
        Route::post('verify-otp',        [AuthController::class, 'verifyOtp']);
        Route::post('forgot-password',   [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',    [AuthController::class, 'resetPassword']);
    });

    Route::get('properties',             [PropertyController::class, 'index']);
    Route::get('properties/featured',    [PropertyController::class, 'featured']);
    Route::get('properties/{id}',        [PropertyController::class, 'show']);
    Route::get('properties/{id}/reviews',[ReviewController::class, 'propertyReviews']);

    /* ── Authenticated ── */
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout',       [AuthController::class, 'logout']);
        Route::get('auth/me',            [AuthController::class, 'me']);

        /* Profile */
        Route::get('profile',            [ProfileController::class, 'show']);
        Route::put('profile',            [ProfileController::class, 'update']);
        Route::post('profile/avatar',    [ProfileController::class, 'updateAvatar']);
        Route::put('profile/password',   [ProfileController::class, 'changePassword']);

        /* Properties (agent/admin) */
        Route::post('properties',        [PropertyController::class, 'store']);
        Route::put('properties/{id}',    [PropertyController::class, 'update']);
        Route::delete('properties/{id}', [PropertyController::class, 'destroy']);
        Route::post('properties/{id}/images', [PropertyController::class, 'uploadImages']);

        /* Bookings */
        Route::get('bookings',           [BookingController::class, 'index']);
        Route::post('bookings',          [BookingController::class, 'store']);
        Route::get('bookings/{ref}',     [BookingController::class, 'show']);
        Route::put('bookings/{ref}/cancel', [BookingController::class, 'cancel']);
        Route::put('bookings/{ref}/confirm', [BookingController::class, 'confirm']);

        /* Payments */
        Route::post('payments/initiate', [PaymentController::class, 'initiate']);
        Route::post('payments/mtn/callback', [PaymentController::class, 'mtnCallback']);
        Route::post('payments/airtel/callback', [PaymentController::class, 'airtelCallback']);
        Route::get('payments/{ref}/status', [PaymentController::class, 'status']);

        /* Favorites */
        Route::get('favorites',          [FavoriteController::class, 'index']);
        Route::post('favorites/{id}',    [FavoriteController::class, 'toggle']);

        /* Messages */
        Route::get('messages',           [MessageController::class, 'conversations']);
        Route::get('messages/{userId}',  [MessageController::class, 'thread']);
        Route::post('messages',          [MessageController::class, 'send']);
        Route::put('messages/{id}/read', [MessageController::class, 'markRead']);

        /* Reviews */
        Route::post('reviews',           [ReviewController::class, 'store']);
        Route::put('reviews/{id}',       [ReviewController::class, 'update']);

        /* Notifications */
        Route::get('notifications',      [NotificationController::class, 'index']);
        Route::put('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);
    });
});
