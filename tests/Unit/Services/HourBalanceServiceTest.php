<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\Wallet;
use App\Services\HourBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HourBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private HourBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HourBalanceService();
    }

    /** @test */
    public function it_calculates_positive_balance(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Add 120 minutes credit
        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 120,
            'occurred_at' => now(),
        ]);

        // Act
        $balance = $this->service->calculateBalance($wallet);

        // Assert
        $this->assertEquals(120, $balance);
    }

    /** @test */
    public function it_calculates_negative_balance(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Add 60 minutes credit
        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 60,
            'occurred_at' => now(),
        ]);

        // Debit 120 minutes (more than available)
        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_DEBIT,
            'minutes' => 120,
            'occurred_at' => now(),
        ]);

        // Act
        $balance = $this->service->calculateBalance($wallet);

        // Assert
        $this->assertEquals(-60, $balance);
        $this->assertTrue($this->service->hasDebt($wallet));
        $this->assertEquals(60, $this->service->getDebtAmount($wallet));
    }

    /** @test */
    public function it_calculates_balance_for_period(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');

        // Transaction inside period
        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 120,
            'occurred_at' => Carbon::parse('2024-01-15'),
        ]);

        // Transaction outside period
        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 60,
            'occurred_at' => Carbon::parse('2024-02-15'),
        ]);

        // Act
        $balance = $this->service->calculateBalanceForPeriod($wallet, $startDate, $endDate);

        // Assert
        $this->assertEquals(120, $balance);
    }

    /** @test */
    public function it_detects_debt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_DEBIT,
            'minutes' => 100,
            'occurred_at' => now(),
        ]);

        // Act & Assert
        $this->assertTrue($this->service->hasDebt($wallet));
        $this->assertEquals(100, $this->service->getDebtAmount($wallet));
    }

    /** @test */
    public function it_returns_zero_debt_when_balance_is_positive(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
            'occurred_at' => now(),
        ]);

        // Act & Assert
        $this->assertFalse($this->service->hasDebt($wallet));
        $this->assertEquals(0, $this->service->getDebtAmount($wallet));
    }

    /** @test */
    public function it_checks_sufficient_balance(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 120,
            'occurred_at' => now(),
        ]);

        // Act & Assert
        $this->assertTrue($this->service->hasSufficientBalance($wallet, 100));
        $this->assertFalse($this->service->hasSufficientBalance($wallet, 150));
    }

    /** @test */
    public function it_formats_minutes_to_hours(): void
    {
        // Act
        $result1 = $this->service->formatMinutesToHours(125);
        $result2 = $this->service->formatMinutesToHours(-125);

        // Assert
        $this->assertEquals(['hours' => 2, 'minutes' => 5], $result1);
        $this->assertEquals(['hours' => 2, 'minutes' => 5], $result2);
    }

    /** @test */
    public function it_handles_transfer_transactions(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        // Initial credit to wallet1
        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 200,
            'occurred_at' => now(),
        ]);

        // Transfer 100 minutes from wallet1 to wallet2
        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_TRANSFER_OUT,
            'minutes' => 100,
            'occurred_at' => now(),
        ]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet2->id,
            'type' => HourTransaction::TYPE_TRANSFER_IN,
            'minutes' => 100,
            'occurred_at' => now(),
        ]);

        // Act
        $balance1 = $this->service->calculateBalance($wallet1);
        $balance2 = $this->service->calculateBalance($wallet2);

        // Assert
        $this->assertEquals(100, $balance1);
        $this->assertEquals(100, $balance2);
    }
}
