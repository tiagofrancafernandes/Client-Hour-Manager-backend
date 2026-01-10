<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HourTransaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HourLedgerService
{
    /**
     * Add credit to a wallet.
     *
     * @param Wallet $wallet
     * @param int $minutes Must be greater than 0
     * @param string|null $description Optional description visible to client
     * @param string|null $internalNote Optional internal note (admin-only)
     * @param Carbon|null $occurredAt When the transaction occurred (defaults to now)
     * @return HourTransaction
     * @throws InvalidArgumentException
     */
    public function addCredit(
        Wallet $wallet,
        int $minutes,
        ?string $description = null,
        ?string $internalNote = null,
        ?Carbon $occurredAt = null
    ): HourTransaction {
        $this->validateMinutes($minutes);

        return DB::transaction(fn () => HourTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => $minutes,
            'description' => $description,
            'internal_note' => $internalNote,
            'occurred_at' => $occurredAt ?? now(),
        ]));
    }

    /**
     * Add debit to a wallet.
     *
     * Debit is always allowed, even if it results in negative balance.
     * This follows the ledger-first principle where balance can go negative.
     *
     * @param Wallet $wallet
     * @param int $minutes Must be greater than 0
     * @param string|null $description Optional description visible to client
     * @param string|null $internalNote Optional internal note (admin-only)
     * @param Carbon|null $occurredAt When the transaction occurred (defaults to now)
     * @return HourTransaction
     * @throws InvalidArgumentException
     */
    public function addDebit(
        Wallet $wallet,
        int $minutes,
        ?string $description = null,
        ?string $internalNote = null,
        ?Carbon $occurredAt = null
    ): HourTransaction {
        $this->validateMinutes($minutes);

        return DB::transaction(fn () => HourTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_DEBIT,
            'minutes' => $minutes,
            'description' => $description,
            'internal_note' => $internalNote,
            'occurred_at' => $occurredAt ?? now(),
        ]));
    }

    /**
     * Validate that minutes is greater than 0.
     *
     * @param int $minutes
     * @throws InvalidArgumentException
     */
    private function validateMinutes(int $minutes): void
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException(__('messages.transaction.invalid_amount'));
        }
    }
}
