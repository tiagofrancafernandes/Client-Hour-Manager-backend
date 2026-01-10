<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\Wallet;
use App\Services\HourBalanceService;
use App\Services\WalletTransferService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WalletTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletTransferService $transferService;
    private HourBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferService = new WalletTransferService();
        $this->balanceService = new HourBalanceService();
    }

    /** @test */
    public function itTransfersMinutesBetweenWallets(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        // Add initial balance to wallet1
        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 200,
            'occurred_at' => now(),
        ]);

        // Act
        $result = $this->transferService->transfer($wallet1, $wallet2, 100);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('source', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertInstanceOf(HourTransaction::class, $result['source']);
        $this->assertInstanceOf(HourTransaction::class, $result['target']);

        // Verify source transaction
        $this->assertEquals(HourTransaction::TYPE_TRANSFER_OUT, $result['source']->type);
        $this->assertEquals(100, $result['source']->minutes);
        $this->assertEquals($wallet1->id, $result['source']->wallet_id);

        // Verify target transaction
        $this->assertEquals(HourTransaction::TYPE_TRANSFER_IN, $result['target']->type);
        $this->assertEquals(100, $result['target']->minutes);
        $this->assertEquals($wallet2->id, $result['target']->wallet_id);

        // Verify balances
        $this->assertEquals(100, $this->balanceService->calculateBalance($wallet1));
        $this->assertEquals(100, $this->balanceService->calculateBalance($wallet2));
    }

    /** @test */
    public function itAllowsTransferWithSufficientBalance(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 150,
            'occurred_at' => now(),
        ]);

        // Act
        $this->transferService->transfer($wallet1, $wallet2, 100);

        // Assert
        $this->assertEquals(50, $this->balanceService->calculateBalance($wallet1));
        $this->assertEquals(100, $this->balanceService->calculateBalance($wallet2));
    }

    /** @test */
    public function itAllowsTransferCausingNegativeBalance(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 50,
            'occurred_at' => now(),
        ]);

        // Act - Transfer more than available balance
        $this->transferService->transfer($wallet1, $wallet2, 100);

        // Assert
        $this->assertEquals(-50, $this->balanceService->calculateBalance($wallet1));
        $this->assertEquals(100, $this->balanceService->calculateBalance($wallet2));
        $this->assertTrue($this->balanceService->hasDebt($wallet1));
    }

    /** @test */
    public function itMaintainsLedgerConsistency(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 200,
            'occurred_at' => now(),
        ]);

        // Act
        $this->transferService->transfer($wallet1, $wallet2, 100);

        // Assert - Total balance across both wallets should remain the same
        $totalBalance = $this->balanceService->calculateBalance($wallet1)
            + $this->balanceService->calculateBalance($wallet2);

        $this->assertEquals(200, $totalBalance);
    }

    /** @test */
    public function itStoresDescriptionAndInternalNote(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $result = $this->transferService->transfer(
            $wallet1,
            $wallet2,
            100,
            'Transfer description',
            'Internal admin note'
        );

        // Assert
        $this->assertEquals('Transfer description', $result['source']->description);
        $this->assertEquals('Internal admin note', $result['source']->internal_note);
        $this->assertEquals('Transfer description', $result['target']->description);
        $this->assertEquals('Internal admin note', $result['target']->internal_note);
    }

    /** @test */
    public function itUsesCustomOccurredAt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);
        $customDate = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $result = $this->transferService->transfer(
            $wallet1,
            $wallet2,
            100,
            null,
            null,
            $customDate
        );

        // Assert
        $this->assertEquals(
            $customDate->format('Y-m-d H:i:s'),
            $result['source']->occurred_at->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $customDate->format('Y-m-d H:i:s'),
            $result['target']->occurred_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function itThrowsExceptionForZeroMinutes(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->transferService->transfer($wallet1, $wallet2, 0);
    }

    /** @test */
    public function itThrowsExceptionForNegativeMinutes(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->transferService->transfer($wallet1, $wallet2, -50);
    }

    /** @test */
    public function itThrowsExceptionForSameWallet(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.wallet.cannot_transfer_to_same'));

        // Act
        $this->transferService->transfer($wallet, $wallet, 100);
    }

    /** @test */
    public function itCreatesAtomicTransaction(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);
        $initialCount = HourTransaction::count();

        // Act
        $this->transferService->transfer($wallet1, $wallet2, 100);

        // Assert - Should create exactly 2 transactions
        $this->assertEquals($initialCount + 2, HourTransaction::count());
    }

    /** @test */
    public function itHandlesMultipleTransfersBetweenSameWallets(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet1 = Wallet::factory()->create(['client_id' => $client->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client->id]);

        HourTransaction::factory()->create([
            'wallet_id' => $wallet1->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 300,
            'occurred_at' => now(),
        ]);

        // Act
        $this->transferService->transfer($wallet1, $wallet2, 100);
        $this->transferService->transfer($wallet1, $wallet2, 50);
        $this->transferService->transfer($wallet2, $wallet1, 30);

        // Assert
        $this->assertEquals(180, $this->balanceService->calculateBalance($wallet1)); // 300 - 100 - 50 + 30
        $this->assertEquals(120, $this->balanceService->calculateBalance($wallet2)); // 100 + 50 - 30
    }
}
