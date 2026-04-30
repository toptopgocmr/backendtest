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
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\AdminPaymentController;

Route::prefix('admin')->name('admin.')->group(function () {

    // ── GUEST ──────────────────────────────────────────────────────────────────
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
    });

    // ── AUTH ADMIN ─────────────────────────────────────────────────────────────
    Route::middleware('auth:admin')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // DASHBOARD
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // PROPERTIES
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::get('/',            [PropertyController::class, 'index'])->name('index');
            Route::get('create',       [PropertyController::class, 'create'])->name('create');
            Route::post('/',           [PropertyController::class, 'store'])->name('store');
            Route::get('{id}',         [PropertyController::class, 'show'])->name('show');
            Route::get('{id}/edit',    [PropertyController::class, 'edit'])->name('edit');
            Route::put('{id}',         [PropertyController::class, 'update'])->name('update');
            Route::put('{id}/approve', [PropertyController::class, 'approve'])->name('approve');
            Route::delete('{id}',      [PropertyController::class, 'destroy'])->name('destroy');
        });

        // BOOKINGS
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/',              [BookingController::class, 'index'])->name('index');
            Route::get('export-csv',     [BookingController::class, 'exportCsv'])->name('export-csv');
            Route::get('{ref}',          [BookingController::class, 'show'])->name('show');
            Route::put('{ref}/confirm',  [BookingController::class, 'confirm'])->name('confirm');
            Route::put('{ref}/cancel',   [BookingController::class, 'cancel'])->name('cancel');   // ✅ FIX Bug 1
            Route::put('{ref}/complete', [BookingController::class, 'complete'])->name('complete');
        });

        // PAYMENTS
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/',              [PaymentController::class, 'index'])->name('index');
            Route::get('export-csv',     [PaymentController::class, 'exportCsv'])->name('export-csv');
            Route::get('{id}/receipt',   [PaymentController::class, 'receipt'])->name('receipt');
            Route::post('{id}/validate', [PaymentController::class, 'validatePayment'])->name('validate');
            Route::post('{id}/reject',   [PaymentController::class, 'rejectPayment'])->name('reject');
            Route::post('{ref}/refund',  [PaymentController::class, 'refund'])->name('refund');
        });

        // USERS
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',            [UserController::class, 'index'])->name('index');
            Route::get('{id}',         [UserController::class, 'show'])->name('show');
            Route::put('{id}/toggle',  [UserController::class, 'toggle'])->name('toggle');
            Route::put('{id}/verify',  [UserController::class, 'verify'])->name('verify');
        });

        // OWNERS
        Route::prefix('owners')->name('owners.')->group(function () {
            Route::get('/',           [OwnerController::class, 'index'])->name('index');
            Route::get('create',      [OwnerController::class, 'create'])->name('create');
            Route::post('/',          [OwnerController::class, 'store'])->name('store');
            Route::get('{id}',        [OwnerController::class, 'show'])->name('show');
            Route::get('{id}/edit',   [OwnerController::class, 'edit'])->name('edit');
            Route::put('{id}',        [OwnerController::class, 'update'])->name('update');
            Route::put('{id}/verify', [OwnerController::class, 'verify'])->name('verify');
            Route::put('{id}/toggle', [OwnerController::class, 'toggle'])->name('toggle');
        });

        // AGENTS
        Route::prefix('agents')->name('agents.')->group(function () {
            Route::get('/',           [AgentController::class, 'index'])->name('index');
            Route::get('create',      [AgentController::class, 'create'])->name('create');
            Route::post('/',          [AgentController::class, 'store'])->name('store');
            Route::get('{id}',        [AgentController::class, 'show'])->name('show');
            Route::get('{id}/edit',   [AgentController::class, 'edit'])->name('edit');
            Route::put('{id}',        [AgentController::class, 'update'])->name('update');
            Route::put('{id}/toggle', [AgentController::class, 'toggle'])->name('toggle');
            Route::delete('{id}',     [AgentController::class, 'destroy'])->name('destroy');
        });

        // STOCK
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::get('/',                   [StockController::class, 'index'])->name('index');
            Route::get('create',              [StockController::class, 'create'])->name('create');
            Route::post('/',                  [StockController::class, 'store'])->name('store');
            Route::get('alerts',              [StockController::class, 'alerts'])->name('alerts');
            Route::get('movements',           [StockController::class, 'movements'])->name('movements');
            Route::get('{id}',                [StockController::class, 'show'])->name('show');
            Route::get('{id}/edit',           [StockController::class, 'edit'])->name('edit');
            Route::put('{id}',                [StockController::class, 'update'])->name('update');
            Route::post('{id}/add',           [StockController::class, 'addStock'])->name('add');
            Route::post('{id}/remove',        [StockController::class, 'removeStock'])->name('remove');
            Route::put('alerts/{id}/resolve', [StockController::class, 'resolveAlert'])->name('alerts.resolve');
        });

        // REVIEWS
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/',           [ReviewController::class, 'index'])->name('index');
            Route::delete('{id}',     [ReviewController::class, 'destroy'])->name('destroy');
            Route::put('{id}/toggle', [ReviewController::class, 'toggle'])->name('toggle');
        });

        // ACCOUNTING
        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');

        // SUPPORT
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/',           [SupportController::class, 'index'])->name('index');
            Route::get('{id}',        [SupportController::class, 'show'])->name('show');
            Route::post('{id}/reply', [SupportController::class, 'reply'])->name('reply');
            Route::put('{id}/close',  [SupportController::class, 'close'])->name('close');
        });

        // MESSAGES
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/',           [MessageController::class, 'index'])->name('index');
            Route::get('{id}',        [MessageController::class, 'show'])->name('show');
            Route::post('{id}/reply', [MessageController::class, 'reply'])->name('reply');
        });

        // SETTINGS
        Route::get('settings', fn () => view('admin.settings'))->name('settings');

        // ── API JSON — ADMIN PAYMENTS ──────────────────────────────────────────
        Route::prefix('api/payments')->name('api.payments.')->group(function () {
            Route::get('/',                    [AdminPaymentController::class, 'index'])->name('index');
            Route::get('{payment}',            [AdminPaymentController::class, 'show'])->name('show');
            Route::patch('{payment}/validate', [AdminPaymentController::class, 'validatePayment'])->name('validate');
            Route::patch('{payment}/reject',   [AdminPaymentController::class, 'reject'])->name('reject');
        });
    });
});
