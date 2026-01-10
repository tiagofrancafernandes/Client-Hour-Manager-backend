<?php

use App\Http\Controllers\Api\V1\TransactionController;
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

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Transaction endpoints
    Route::post('/transactions/credit', [TransactionController::class, 'addCredit'])
        ->name('api.v1.transactions.credit');
    Route::post('/transactions/debit', [TransactionController::class, 'addDebit'])
        ->name('api.v1.transactions.debit');
});
