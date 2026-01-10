<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Wallet;
use Carbon\Carbon;

class HourBalanceService
{
    /**
     * Calculate the total balance for a wallet in minutes.
     *
     * Balance is always derived from the ledger, never stored.
     * Positive balance = client has hours available
     * Negative balance = client owes hours (debt)
     */
    public function calculateBalance(Wallet $wallet): int
    {
        return $wallet->transactions()
            ->get()
            ->sum(fn ($transaction) => $transaction->getSignedMinutes());
    }

    /**
     * Calculate balance for a specific period.
     *
     * @param Wallet $wallet
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int Balance in minutes
     */
    public function calculateBalanceForPeriod(
        Wallet $wallet,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        return $wallet->transactions()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->get()
            ->sum(fn ($transaction) => $transaction->getSignedMinutes());
    }

    /**
     * Check if wallet has negative balance (debt).
     */
    public function hasDebt(Wallet $wallet): bool
    {
        return $this->calculateBalance($wallet) < 0;
    }

    /**
     * Get the debt amount in minutes (always positive).
     * Returns 0 if there's no debt.
     */
    public function getDebtAmount(Wallet $wallet): int
    {
        $balance = $this->calculateBalance($wallet);

        return $balance < 0 ? abs($balance) : 0;
    }

    /**
     * Check if wallet has sufficient balance for a given amount of minutes.
     */
    public function hasSufficientBalance(Wallet $wallet, int $minutes): bool
    {
        return $this->calculateBalance($wallet) >= $minutes;
    }

    /**
     * Format minutes to hours and minutes.
     *
     * @param int $minutes
     * @return array{hours: int, minutes: int}
     */
    public function formatMinutesToHours(int $minutes): array
    {
        $absoluteMinutes = abs($minutes);
        $hours = intdiv($absoluteMinutes, 60);
        $remainingMinutes = $absoluteMinutes % 60;

        return [
            'hours' => $hours,
            'minutes' => $remainingMinutes,
        ];
    }
}
