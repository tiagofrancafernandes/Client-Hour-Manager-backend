<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\Invoice;
use App\Models\Wallet;
use App\Services\HourBalanceService;
use App\Services\HourLedgerService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;
    private HourBalanceService $balanceService;
    private HourLedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balanceService = new HourBalanceService();
        $this->ledgerService = new HourLedgerService();
        $this->invoiceService = new InvoiceService($this->balanceService, $this->ledgerService);
    }

    /** @test */
    public function itCreatesInvoiceFromDebt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Create debt by adding a debit transaction
        $this->ledgerService->addDebit($wallet, 120);

        // Act
        $invoice = $this->invoiceService->createFromDebt($wallet, 100.00, 'Please pay');

        // Assert
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(Invoice::STATUS_OPEN, $invoice->status);
        $this->assertEquals(120, $invoice->minutes);
        $this->assertEquals(100.00, $invoice->price_per_hour);
        $this->assertEquals(200.00, $invoice->total_amount); // 120 minutes = 2 hours * 100
        $this->assertEquals('Please pay', $invoice->client_message);
        $this->assertNull($invoice->paid_at);
    }

    /** @test */
    public function itThrowsExceptionWhenCreatingInvoiceWithNoDebt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Add credit (positive balance)
        $this->ledgerService->addCredit($wallet, 100);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.invoice.no_debt'));

        // Act
        $this->invoiceService->createFromDebt($wallet, 100.00);
    }

    /** @test */
    public function itThrowsExceptionForInvalidPriceWhenCreatingFromDebt(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $this->ledgerService->addDebit($wallet, 120);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->invoiceService->createFromDebt($wallet, 0);
    }

    /** @test */
    public function itCreatesInvoiceForPackage(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act
        $invoice = $this->invoiceService->createForPackage($wallet, 300, 75.50, 'Package purchase');

        // Assert
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(Invoice::STATUS_OPEN, $invoice->status);
        $this->assertEquals(300, $invoice->minutes);
        $this->assertEquals(75.50, $invoice->price_per_hour);
        $this->assertEquals(377.50, $invoice->total_amount); // 300 minutes = 5 hours * 75.50
        $this->assertEquals('Package purchase', $invoice->client_message);
    }

    /** @test */
    public function itThrowsExceptionForInvalidMinutesWhenCreatingForPackage(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->invoiceService->createForPackage($wallet, 0, 100.00);
    }

    /** @test */
    public function itThrowsExceptionForInvalidPriceWhenCreatingForPackage(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->invoiceService->createForPackage($wallet, 100, -50.00);
    }

    /** @test */
    public function itMarksInvoiceAsPaidAndAddsCreditToWallet(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->open()->create([
            'wallet_id' => $wallet->id,
            'minutes' => 120,
        ]);

        $initialBalance = $this->balanceService->calculateBalance($wallet);

        // Act
        $paidInvoice = $this->invoiceService->markAsPaid($invoice);

        // Assert
        $this->assertEquals(Invoice::STATUS_PAID, $paidInvoice->status);
        $this->assertNotNull($paidInvoice->paid_at);

        // Verify credit was added to ledger
        $newBalance = $this->balanceService->calculateBalance($wallet);
        $this->assertEquals($initialBalance + 120, $newBalance);

        // Verify transaction exists
        $transaction = HourTransaction::where('wallet_id', $wallet->id)
            ->where('type', HourTransaction::TYPE_CREDIT)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(120, $transaction->minutes);
    }

    /** @test */
    public function itThrowsExceptionWhenMarkingPaidInvoiceAsPaid(): void
    {
        // Arrange
        $invoice = Invoice::factory()->paid()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.invoice.cannot_modify'));

        // Act
        $this->invoiceService->markAsPaid($invoice);
    }

    /** @test */
    public function itThrowsExceptionWhenMarkingCancelledInvoiceAsPaid(): void
    {
        // Arrange
        $invoice = Invoice::factory()->cancelled()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->invoiceService->markAsPaid($invoice);
    }

    /** @test */
    public function itCancelsOpenInvoice(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->open()->create(['wallet_id' => $wallet->id]);

        $initialTransactionCount = HourTransaction::count();

        // Act
        $cancelledInvoice = $this->invoiceService->cancel($invoice);

        // Assert
        $this->assertEquals(Invoice::STATUS_CANCELLED, $cancelledInvoice->status);

        // Verify NO ledger entry was created
        $this->assertEquals($initialTransactionCount, HourTransaction::count());
    }

    /** @test */
    public function itThrowsExceptionWhenCancellingPaidInvoice(): void
    {
        // Arrange
        $invoice = Invoice::factory()->paid()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->invoiceService->cancel($invoice);
    }

    /** @test */
    public function itThrowsExceptionWhenCancellingAlreadyCancelledInvoice(): void
    {
        // Arrange
        $invoice = Invoice::factory()->cancelled()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->invoiceService->cancel($invoice);
    }

    /** @test */
    public function itDoesNotAlterLedgerHistoryWhenCreatingInvoice(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $this->ledgerService->addDebit($wallet, 120);

        $balanceBeforeInvoice = $this->balanceService->calculateBalance($wallet);
        $transactionCountBefore = HourTransaction::count();

        // Act
        $this->invoiceService->createFromDebt($wallet, 100.00);

        // Assert
        $balanceAfterInvoice = $this->balanceService->calculateBalance($wallet);
        $transactionCountAfter = HourTransaction::count();

        $this->assertEquals($balanceBeforeInvoice, $balanceAfterInvoice);
        $this->assertEquals($transactionCountBefore, $transactionCountAfter);
    }

    /** @test */
    public function itCalculatesTotalAmountCorrectly(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Act - 90 minutes = 1.5 hours * 60.00 = 90.00
        $invoice1 = $this->invoiceService->createForPackage($wallet, 90, 60.00);
        $this->assertEquals(90.00, $invoice1->total_amount);

        // Act - 45 minutes = 0.75 hours * 80.00 = 60.00
        $invoice2 = $this->invoiceService->createForPackage($wallet, 45, 80.00);
        $this->assertEquals(60.00, $invoice2->total_amount);

        // Act - 1 minute = 0.0167 hours * 120.00 = 2.00
        $invoice3 = $this->invoiceService->createForPackage($wallet, 1, 120.00);
        $this->assertEquals(2.00, $invoice3->total_amount);
    }

    /** @test */
    public function paymentFlowFromDebtToCreditIsCorrect(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Client has debt
        $this->ledgerService->addDebit($wallet, 180); // -180 minutes
        $this->assertEquals(-180, $this->balanceService->calculateBalance($wallet));

        // Create invoice from debt
        $invoice = $this->invoiceService->createFromDebt($wallet, 100.00);
        $this->assertEquals(180, $invoice->minutes);

        // Balance should still be negative (invoice doesn't affect balance)
        $this->assertEquals(-180, $this->balanceService->calculateBalance($wallet));

        // Mark as paid (this adds credit to ledger)
        $this->invoiceService->markAsPaid($invoice);

        // Now balance should be zero
        $this->assertEquals(0, $this->balanceService->calculateBalance($wallet));
    }
}
