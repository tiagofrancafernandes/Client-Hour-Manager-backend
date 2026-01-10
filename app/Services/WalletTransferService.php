<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HourTransaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletTransferService
{
    /**
     * Transfer minutes from one wallet to another.
     *
     * This operation is atomic - both transactions are created or none.
     * Creates a transfer_out transaction in source wallet and transfer_in in target wallet.
     * Transfer is always allowed, even if source wallet goes into negative balance.
     *
     * @param Wallet $sourceWallet
     * @param Wallet $targetWallet
     * @param int $minutes Must be greater than 0
     * @param string|null $description Optional description visible to client
     * @param string|null $internalNote Optional internal note (admin-only)
     * @param Carbon|null $occurredAt When the transfer occurred (defaults to now)
     * @return array{source: HourTransaction, target: HourTransaction}
     * @throws InvalidArgumentException
     */
    public function transfer(
        Wallet $sourceWallet,
        Wallet $targetWallet,
        int $minutes,
        ?string $description = null,
        ?string $internalNote = null,
        ?Carbon $occurredAt = null
    ): array {
        $this->validateMinutes($minutes);
        $this->validateDifferentWallets($sourceWallet, $targetWallet);

        return DB::transaction(function () use (
            $sourceWallet,
            $targetWallet,
            $minutes,
            $description,
            $internalNote,
            $occurredAt
        ) {
            $occurred = $occurredAt ?? now();

            // Create transfer_out transaction in source wallet
            $sourceTransaction = HourTransaction::create([
                'wallet_id' => $sourceWallet->id,
                'type' => HourTransaction::TYPE_TRANSFER_OUT,
                'minutes' => $minutes,
                'description' => $description,
                'internal_note' => $internalNote,
                'occurred_at' => $occurred,
            ]);

            // Create transfer_in transaction in target wallet
            $targetTransaction = HourTransaction::create([
                'wallet_id' => $targetWallet->id,
                'type' => HourTransaction::TYPE_TRANSFER_IN,
                'minutes' => $minutes,
                'description' => $description,
                'internal_note' => $internalNote,
                'occurred_at' => $occurred,
            ]);

            return [
                'source' => $sourceTransaction,
                'target' => $targetTransaction,
            ];
        });
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

    /**
     * Validate that source and target wallets are different.
     *
     * @param Wallet $sourceWallet
     * @param Wallet $targetWallet
     * @throws InvalidArgumentException
     */
    private function validateDifferentWallets(Wallet $sourceWallet, Wallet $targetWallet): void
    {
        if ($sourceWallet->id === $targetWallet->id) {
            throw new InvalidArgumentException(__('messages.wallet.cannot_transfer_to_same'));
        }
    }
}
