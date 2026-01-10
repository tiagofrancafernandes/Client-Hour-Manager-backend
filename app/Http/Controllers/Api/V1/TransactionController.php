<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Wallet;
use App\Services\HourLedgerService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly HourLedgerService $ledgerService
    ) {
    }

    /**
     * Add credit to a wallet.
     *
     * Manually add credit (hours/minutes) to a wallet's balance.
     * Only administrators can perform this operation.
     *
     * @operationId addCredit
     * @tag Transactions
     *
     * @response 201 {
     *   "message": "Credit added successfully.",
     *   "data": {
     *     "id": 1,
     *     "wallet_id": 1,
     *     "type": "credit",
     *     "minutes": 300,
     *     "description": "Monthly hours package",
     *     "occurred_at": "2024-01-10T10:00:00Z",
     *     "created_at": "2024-01-10T10:00:00Z"
     *   }
     * }
     *
     * @response 401 {"message": "Unauthenticated."}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 422 {"message": "The given data was invalid.", "errors": {...}}
     */
    public function addCredit(StoreTransactionRequest $request): JsonResponse
    {
        $wallet = Wallet::findOrFail($request->input('wallet_id'));

        $transaction = $this->ledgerService->addCredit(
            wallet: $wallet,
            minutes: (int) $request->input('minutes'),
            description: $request->input('description'),
            internalNote: $request->input('internal_note'),
            occurredAt: $request->input('occurred_at')
                ? \Carbon\Carbon::parse($request->input('occurred_at'))
                : null
        );

        return response()->json([
            'message' => __('messages.transaction.credit_added'),
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    /**
     * Add debit to a wallet.
     *
     * Manually add debit (subtract hours/minutes) from a wallet's balance.
     * Only administrators can perform this operation.
     * Debits are always allowed, even if they result in negative balance.
     *
     * @operationId addDebit
     * @tag Transactions
     *
     * @response 201 {
     *   "message": "Debit added successfully.",
     *   "data": {
     *     "id": 2,
     *     "wallet_id": 1,
     *     "type": "debit",
     *     "minutes": 150,
     *     "description": "Time tracked for project",
     *     "occurred_at": "2024-01-10T10:00:00Z",
     *     "created_at": "2024-01-10T10:00:00Z"
     *   }
     * }
     *
     * @response 401 {"message": "Unauthenticated."}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 422 {"message": "The given data was invalid.", "errors": {...}}
     */
    public function addDebit(StoreTransactionRequest $request): JsonResponse
    {
        $wallet = Wallet::findOrFail($request->input('wallet_id'));

        $transaction = $this->ledgerService->addDebit(
            wallet: $wallet,
            minutes: (int) $request->input('minutes'),
            description: $request->input('description'),
            internalNote: $request->input('internal_note'),
            occurredAt: $request->input('occurred_at')
                ? \Carbon\Carbon::parse($request->input('occurred_at'))
                : null
        );

        return response()->json([
            'message' => __('messages.transaction.debit_added'),
            'data' => new TransactionResource($transaction),
        ], 201);
    }
}
