<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\Wallet;
use App\Services\HourBalanceService;
use App\Services\HourLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class HourLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private HourLedgerService $ledgerService;
    private HourBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = new HourLedgerService();
        $this->balanceService = new HourBalanceService();
    }

    /** @test */
    public function itAddsCredit(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $transaction = $this->ledgerService->addCredit(
            $wallet,
            120,
            'Credit for service',
            'Internal note for admin'
        );

        // Assert
        $this->assertInstanceOf(HourTransaction::class, $transaction);
        $this->assertEquals(HourTransaction::TYPE_CREDIT, $transaction->type);
        $this->assertEquals(120, $transaction->minutes);
        $this->assertEquals('Credit for service', $transaction->description);
        $this->assertEquals('Internal note for admin', $transaction->internal_note);
        $this->assertEquals(120, $this->balanceService->calculateBalance($wallet));
    }

    /** @test */
    public function itAddsDebit(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $transaction = $this->ledgerService->addDebit(
            $wallet,
            60,
            'Debit for work done',
            'Internal tracking'
        );

        // Assert
        $this->assertInstanceOf(HourTransaction::class, $transaction);
        $this->assertEquals(HourTransaction::TYPE_DEBIT, $transaction->type);
        $this->assertEquals(60, $transaction->minutes);
        $this->assertEquals('Debit for work done', $transaction->description);
        $this->assertEquals('Internal tracking', $transaction->internal_note);
        $this->assertEquals(-60, $this->balanceService->calculateBalance($wallet));
    }

    /** @test */
    public function itAllowsDebitWithoutBalance(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act - Debit without any credit first
        $transaction = $this->ledgerService->addDebit($wallet, 100);

        // Assert
        $this->assertEquals(HourTransaction::TYPE_DEBIT, $transaction->type);
        $this->assertEquals(-100, $this->balanceService->calculateBalance($wallet));
        $this->assertTrue($this->balanceService->hasDebt($wallet));
    }

    /** @test */
    public function itAllowsCreditAfterDebt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Create debt first
        $this->ledgerService->addDebit($wallet, 100);

        // Act - Add credit to reduce debt
        $this->ledgerService->addCredit($wallet, 60);

        // Assert
        $balance = $this->balanceService->calculateBalance($wallet);
        $this->assertEquals(-40, $balance);
        $this->assertTrue($this->balanceService->hasDebt($wallet));
        $this->assertEquals(40, $this->balanceService->getDebtAmount($wallet));
    }

    /** @test */
    public function itThrowsExceptionForZeroMinutes(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->ledgerService->addCredit($wallet, 0);
    }

    /** @test */
    public function itThrowsExceptionForNegativeMinutes(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->ledgerService->addDebit($wallet, -50);
    }

    /** @test */
    public function itStoresInternalNoteCorrectly(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $transaction = $this->ledgerService->addCredit(
            $wallet,
            100,
            'Visible description',
            'Secret admin note'
        );

        // Assert
        $this->assertEquals('Visible description', $transaction->description);
        $this->assertEquals('Secret admin note', $transaction->internal_note);
    }

    /** @test */
    public function itAllowsNullDescriptionAndNote(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $transaction = $this->ledgerService->addCredit($wallet, 100);

        // Assert
        $this->assertNull($transaction->description);
        $this->assertNull($transaction->internal_note);
    }

    /** @test */
    public function itUsesCustomOccurredAt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $customDate = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $transaction = $this->ledgerService->addCredit(
            $wallet,
            100,
            null,
            null,
            $customDate
        );

        // Assert
        $this->assertEquals($customDate->format('Y-m-d H:i:s'), $transaction->occurred_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function itUsesNowWhenOccurredAtIsNull(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $beforeCreation = now()->subSecond();

        // Act
        $transaction = $this->ledgerService->addCredit($wallet, 100);
        $afterCreation = now()->addSecond();

        // Assert
        $this->assertTrue($transaction->occurred_at->between($beforeCreation, $afterCreation));
    }

    /** @test */
    public function itCreatesTransactionInsideDatabaseTransaction(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $initialCount = HourTransaction::count();

        // Act
        $this->ledgerService->addCredit($wallet, 100);

        // Assert
        $this->assertEquals($initialCount + 1, HourTransaction::count());
    }

    /** @test */
    public function itMaintainsCorrectBalanceAfterMultipleOperations(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $this->ledgerService->addCredit($wallet, 200);  // +200 = 200
        $this->ledgerService->addDebit($wallet, 50);    // -50 = 150
        $this->ledgerService->addCredit($wallet, 100);  // +100 = 250
        $this->ledgerService->addDebit($wallet, 150);   // -150 = 100

        // Assert
        $this->assertEquals(100, $this->balanceService->calculateBalance($wallet));
    }
}
