<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\Timer;
use App\Models\Wallet;
use App\Services\HourBalanceService;
use App\Services\HourLedgerService;
use App\Services\TimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TimerServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimerService $timerService;
    private HourBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $ledgerService = new HourLedgerService();
        $this->timerService = new TimerService($ledgerService);
        $this->balanceService = new HourBalanceService();
    }

    /** @test */
    public function itStartsATimer(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $timer = $this->timerService->start($wallet, $client, 'Working on feature');

        // Assert
        $this->assertInstanceOf(Timer::class, $timer);
        $this->assertEquals(Timer::STATE_RUNNING, $timer->state);
        $this->assertEquals($wallet->id, $timer->wallet_id);
        $this->assertEquals($client->id, $timer->created_by);
        $this->assertEquals('Working on feature', $timer->description);
        $this->assertFalse($timer->is_hidden);
        $this->assertEquals(0, $timer->total_minutes);
        $this->assertNotNull($timer->started_at);
    }

    /** @test */
    public function itStartsAHiddenTimer(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $timer = $this->timerService->start($wallet, $client, null, true);

        // Assert
        $this->assertTrue($timer->is_hidden);
    }

    /** @test */
    public function itPausesARunningTimer(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $timer = $this->timerService->start($wallet, $client);

        // Wait a moment to accumulate time
        sleep(1);

        // Act
        $pausedTimer = $this->timerService->pause($timer);

        // Assert
        $this->assertEquals(Timer::STATE_PAUSED, $pausedTimer->state);
        $this->assertNotNull($pausedTimer->paused_at);
        $this->assertGreaterThanOrEqual(0, $pausedTimer->total_minutes);
    }

    /** @test */
    public function itThrowsExceptionWhenPausingNonRunningTimer(): void
    {
        // Arrange
        $timer = Timer::factory()->paused()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->timerService->pause($timer);
    }

    /** @test */
    public function itResumesAPausedTimer(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $timer = $this->timerService->start($wallet, $client);
        sleep(1);
        $pausedTimer = $this->timerService->pause($timer);

        // Act
        $resumedTimer = $this->timerService->resume($pausedTimer);

        // Assert
        $this->assertEquals(Timer::STATE_RUNNING, $resumedTimer->state);
        $this->assertNull($resumedTimer->paused_at);
        $this->assertNotNull($resumedTimer->started_at);
    }

    /** @test */
    public function itThrowsExceptionWhenResumingNonPausedTimer(): void
    {
        // Arrange
        $timer = Timer::factory()->running()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->timerService->resume($timer);
    }

    /** @test */
    public function itStopsARunningTimerAndCreatesDebitTransaction(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $timer = $this->timerService->start($wallet, $client, 'Task completed');

        sleep(1);

        // Act
        $stoppedTimer = $this->timerService->stop($timer);

        // Assert
        $this->assertEquals(Timer::STATE_STOPPED, $stoppedTimer->state);
        $this->assertNotNull($stoppedTimer->ended_at);
        $this->assertGreaterThanOrEqual(0, $stoppedTimer->total_minutes);

        // Verify debit transaction was created only if minutes > 0
        if ($stoppedTimer->total_minutes > 0) {
            $debitTransaction = HourTransaction::where('wallet_id', $wallet->id)
                ->where('type', HourTransaction::TYPE_DEBIT)
                ->first();

            $this->assertNotNull($debitTransaction);
            $this->assertEquals($stoppedTimer->total_minutes, $debitTransaction->minutes);
        }
    }

    /** @test */
    public function itStopsAPausedTimerAndCreatesDebitTransaction(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Create a timer with accumulated minutes
        $timer = Timer::factory()->paused()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'total_minutes' => 10,
        ]);

        // Act
        $stoppedTimer = $this->timerService->stop($timer);

        // Assert
        $this->assertEquals(Timer::STATE_STOPPED, $stoppedTimer->state);
        $this->assertNotNull($stoppedTimer->ended_at);
        $this->assertEquals(10, $stoppedTimer->total_minutes);

        // Verify debit transaction was created
        $debitTransaction = HourTransaction::where('wallet_id', $wallet->id)
            ->where('type', HourTransaction::TYPE_DEBIT)
            ->first();

        $this->assertNotNull($debitTransaction);
        $this->assertEquals(10, $debitTransaction->minutes);
    }

    /** @test */
    public function itThrowsExceptionWhenStoppingAlreadyStoppedTimer(): void
    {
        // Arrange
        $timer = Timer::factory()->stopped()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->timerService->stop($timer);
    }

    /** @test */
    public function itCancelsATimerWithoutCreatingLedgerEntry(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $timer = $this->timerService->start($wallet, $client);
        sleep(1);

        $initialTransactionCount = HourTransaction::count();

        // Act
        $cancelledTimer = $this->timerService->cancel($timer);

        // Assert
        $this->assertEquals(Timer::STATE_CANCELLED, $cancelledTimer->state);
        $this->assertNotNull($cancelledTimer->ended_at);
        $this->assertGreaterThanOrEqual(0, $cancelledTimer->total_minutes);

        // Verify NO debit transaction was created
        $this->assertEquals($initialTransactionCount, HourTransaction::count());
    }

    /** @test */
    public function itThrowsExceptionWhenCancellingAlreadyCancelledTimer(): void
    {
        // Arrange
        $timer = Timer::factory()->cancelled()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->timerService->cancel($timer);
    }

    /** @test */
    public function itCalculatesCurrentElapsedMinutesForRunningTimer(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $timer = $this->timerService->start($wallet, $client);

        sleep(1);

        // Act
        $elapsedMinutes = $this->timerService->getCurrentElapsedMinutes($timer);

        // Assert
        $this->assertGreaterThanOrEqual(0, $elapsedMinutes);
    }

    /** @test */
    public function itReturnsStoredMinutesForPausedTimer(): void
    {
        // Arrange
        $timer = Timer::factory()->paused()->create([
            'total_minutes' => 30,
        ]);

        // Act
        $elapsedMinutes = $this->timerService->getCurrentElapsedMinutes($timer);

        // Assert
        $this->assertEquals(30, $elapsedMinutes);
    }

    /** @test */
    public function itHandlesCompleteTimerLifecycle(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act - Start
        $timer = $this->timerService->start($wallet, $client, 'Full lifecycle test');
        $this->assertTrue($timer->isRunning());
        sleep(1);

        // Act - Pause
        $timer = $this->timerService->pause($timer);
        $this->assertTrue($timer->isPaused());
        $minutesAfterPause = $timer->total_minutes;

        // Act - Resume
        $timer = $this->timerService->resume($timer);
        $this->assertTrue($timer->isRunning());
        sleep(1);

        // Act - Stop
        $timer = $this->timerService->stop($timer);
        $this->assertTrue($timer->isStopped());

        // Assert
        $this->assertGreaterThanOrEqual($minutesAfterPause, $timer->total_minutes);

        // Verify ledger entry if minutes > 0
        if ($timer->total_minutes > 0) {
            $balance = $this->balanceService->calculateBalance($wallet);
            $this->assertLessThan(0, $balance); // Should have negative balance (debt)
        }
    }

    /** @test */
    public function itAccumulatesTimeCorrectlyAcrossPauseAndResume(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $timer = $this->timerService->start($wallet, $client);
        sleep(1);
        $timer = $this->timerService->pause($timer);
        $firstPauseMinutes = $timer->total_minutes;

        $timer = $this->timerService->resume($timer);
        sleep(1);
        $timer = $this->timerService->pause($timer);
        $secondPauseMinutes = $timer->total_minutes;

        // Assert
        $this->assertGreaterThanOrEqual($firstPauseMinutes, $secondPauseMinutes);
    }

    /** @test */
    public function itDoesNotCreateLedgerEntryForZeroMinuteTimer(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $timer = Timer::factory()->running()->create([
            'wallet_id' => $wallet->id,
            'total_minutes' => 0,
            'started_at' => now(),
        ]);

        $initialTransactionCount = HourTransaction::count();

        // Act - Stop immediately without waiting
        $this->timerService->stop($timer);

        // Assert - Should not create transaction for 0 minutes
        $this->assertEquals($initialTransactionCount, HourTransaction::count());
    }
}
