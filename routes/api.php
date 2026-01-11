<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PackagePurchaseController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\TimerController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

Route::prefix('v1')->group(function () {
    // Authentication endpoints (public)
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->name('api.v1.auth.login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints
        Route::get('/auth/info', [AuthController::class, 'info'])
            ->name('api.v1.auth.info');
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        // Permissions endpoints (read-only, cached)
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('api.v1.permissions.index');

        // Roles endpoints
        Route::apiResource('roles', RoleController::class);

        // Users endpoints
        Route::apiResource('users', UserController::class);
        Route::post('/users/{user}/assign-roles', [UserController::class, 'assignRoles'])
            ->name('api.v1.users.assign-roles');

        // Client endpoints
        Route::apiResource('clients', ClientController::class);

        // Wallet endpoints
        Route::apiResource('wallets', WalletController::class);
        Route::post('/wallets/{wallet}/archive', [WalletController::class, 'archive'])
            ->name('api.v1.wallets.archive');
        Route::post('/wallets/{wallet}/unarchive', [WalletController::class, 'unarchive'])
            ->name('api.v1.wallets.unarchive');

        // Transaction endpoints
        Route::post('/transactions/credit', [TransactionController::class, 'addCredit'])
            ->name('api.v1.transactions.credit');
        Route::post('/transactions/debit', [TransactionController::class, 'addDebit'])
            ->name('api.v1.transactions.debit');

        // Timer endpoints
        Route::apiResource('timers', TimerController::class)->except(['update', 'destroy']);
        Route::post('/timers/{timer}/pause', [TimerController::class, 'pause'])
            ->name('api.v1.timers.pause');
        Route::post('/timers/{timer}/resume', [TimerController::class, 'resume'])
            ->name('api.v1.timers.resume');
        Route::post('/timers/{timer}/stop', [TimerController::class, 'stop'])
            ->name('api.v1.timers.stop');
        Route::post('/timers/{timer}/cancel', [TimerController::class, 'cancel'])
            ->name('api.v1.timers.cancel');

        // Invoice endpoints
        Route::apiResource('invoices', InvoiceController::class)->except(['update', 'destroy']);
        Route::post('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])
            ->name('api.v1.invoices.mark-as-paid');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
            ->name('api.v1.invoices.cancel');

        // Package purchase endpoints
        Route::post('/packages/purchase', [PackagePurchaseController::class, 'initiate'])
            ->name('api.v1.packages.purchase');
    });
});

// Payment gateway webhooks (no auth required - verified by signature)
Route::post('/webhooks/payment/{provider}', [PackagePurchaseController::class, 'webhook'])
    ->name('api.webhooks.payment');
