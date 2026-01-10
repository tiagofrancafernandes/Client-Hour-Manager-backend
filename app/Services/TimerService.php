<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Timer;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TimerService
{
    public function __construct(
        private readonly HourLedgerService $ledgerService
    ) {
    }

    /**
     * Start a new timer for a wallet.
     *
     * @param Wallet $wallet
     * @param Client|null $creator
     * @param string|null $description
     * @param bool $isHidden
     * @return Timer
     */
    public function start(
        Wallet $wallet,
        ?Client $creator = null,
        ?string $description = null,
        bool $isHidden = false
    ): Timer {
        return DB::transaction(fn () => Timer::create([
            'wallet_id' => $wallet->id,
            'created_by' => $creator?->id,
            'state' => Timer::STATE_RUNNING,
            'description' => $description,
            'is_hidden' => $isHidden,
            'started_at' => now(),
            'total_minutes' => 0,
        ]));
    }

    /**
     * Pause a running timer.
     *
     * @param Timer $timer
     * @return Timer
     * @throws InvalidArgumentException
     */
    public function pause(Timer $timer): Timer
    {
        if (!$timer->isRunning()) {
            throw new InvalidArgumentException(__('messages.timer.invalid_state'));
        }

        return DB::transaction(function () use ($timer) {
            $now = now();
            $elapsedMinutes = $this->calculateElapsedMinutes($timer->started_at, $now);

            $timer->update([
                'state' => Timer::STATE_PAUSED,
                'paused_at' => $now,
                'total_minutes' => $timer->total_minutes + $elapsedMinutes,
            ]);

            return $timer->fresh();
        });
    }

    /**
     * Resume a paused timer.
     *
     * @param Timer $timer
     * @return Timer
     * @throws InvalidArgumentException
     */
    public function resume(Timer $timer): Timer
    {
        if (!$timer->isPaused()) {
            throw new InvalidArgumentException(__('messages.timer.invalid_state'));
        }

        return DB::transaction(function () use ($timer) {
            $timer->update([
                'state' => Timer::STATE_RUNNING,
                'started_at' => now(),
                'paused_at' => null,
            ]);

            return $timer->fresh();
        });
    }

    /**
     * Stop a timer and generate a debit transaction.
     *
     * Stopping a timer creates a debit transaction in the wallet's ledger.
     *
     * @param Timer $timer
     * @return Timer
     * @throws InvalidArgumentException
     */
    public function stop(Timer $timer): Timer
    {
        if ($timer->isStopped() || $timer->isCancelled()) {
            throw new InvalidArgumentException(__('messages.timer.invalid_state'));
        }

        return DB::transaction(function () use ($timer) {
            $now = now();
            $totalMinutes = $timer->total_minutes;

            // Add elapsed time if timer is running
            if ($timer->isRunning()) {
                $elapsedMinutes = $this->calculateElapsedMinutes($timer->started_at, $now);
                $totalMinutes += $elapsedMinutes;
            }

            // Update timer state
            $timer->update([
                'state' => Timer::STATE_STOPPED,
                'ended_at' => $now,
                'total_minutes' => $totalMinutes,
            ]);

            // Create debit transaction in the wallet's ledger
            if ($totalMinutes > 0) {
                $this->ledgerService->addDebit(
                    $timer->wallet,
                    $totalMinutes,
                    $timer->description ?? __('messages.timer.debit_description'),
                    null,
                    $now
                );
            }

            return $timer->fresh();
        });
    }

    /**
     * Cancel a timer without generating a ledger entry.
     *
     * Cancelled timers do not affect the wallet's balance.
     *
     * @param Timer $timer
     * @return Timer
     * @throws InvalidArgumentException
     */
    public function cancel(Timer $timer): Timer
    {
        if ($timer->isStopped() || $timer->isCancelled()) {
            throw new InvalidArgumentException(__('messages.timer.invalid_state'));
        }

        return DB::transaction(function () use ($timer) {
            $now = now();
            $totalMinutes = $timer->total_minutes;

            // Add elapsed time if timer is running
            if ($timer->isRunning()) {
                $elapsedMinutes = $this->calculateElapsedMinutes($timer->started_at, $now);
                $totalMinutes += $elapsedMinutes;
            }

            $timer->update([
                'state' => Timer::STATE_CANCELLED,
                'ended_at' => $now,
                'total_minutes' => $totalMinutes,
            ]);

            return $timer->fresh();
        });
    }

    /**
     * Calculate elapsed minutes between two timestamps.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    private function calculateElapsedMinutes(Carbon $start, Carbon $end): int
    {
        return (int) $start->diffInMinutes($end);
    }

    /**
     * Get current elapsed minutes for a running timer.
     *
     * @param Timer $timer
     * @return int
     */
    public function getCurrentElapsedMinutes(Timer $timer): int
    {
        if ($timer->isRunning()) {
            return $timer->total_minutes + $this->calculateElapsedMinutes($timer->started_at, now());
        }

        return $timer->total_minutes;
    }
}
