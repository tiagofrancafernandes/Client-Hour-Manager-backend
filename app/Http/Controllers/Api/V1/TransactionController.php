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
