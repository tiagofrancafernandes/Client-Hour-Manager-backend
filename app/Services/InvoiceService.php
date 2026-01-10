<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(
        private readonly HourBalanceService $balanceService,
        private readonly HourLedgerService $ledgerService
    ) {
    }

    /**
     * Create an invoice from negative balance (debt).
     *
     * @param Wallet $wallet
     * @param float $pricePerHour
     * @param string|null $clientMessage
     * @return Invoice
     * @throws InvalidArgumentException
     */
    public function createFromDebt(
        Wallet $wallet,
        float $pricePerHour,
        ?string $clientMessage = null
    ): Invoice {
        if ($pricePerHour <= 0) {
            throw new InvalidArgumentException(__('messages.invoice.invalid_price'));
        }

        $balance = $this->balanceService->calculateBalance($wallet);

        if ($balance >= 0) {
            throw new InvalidArgumentException(__('messages.invoice.no_debt'));
        }

        $debtMinutes = abs($balance);

        return $this->createInvoice(
            $wallet,
            $debtMinutes,
            $pricePerHour,
            $clientMessage
        );
    }

    /**
     * Create an invoice for package purchase.
     *
     * @param Wallet $wallet
     * @param int $minutes
     * @param float $pricePerHour
     * @param string|null $clientMessage
     * @return Invoice
     * @throws InvalidArgumentException
     */
    public function createForPackage(
        Wallet $wallet,
        int $minutes,
        float $pricePerHour,
        ?string $clientMessage = null
    ): Invoice {
        if ($minutes <= 0) {
            throw new InvalidArgumentException(__('messages.transaction.invalid_amount'));
        }

        if ($pricePerHour <= 0) {
            throw new InvalidArgumentException(__('messages.invoice.invalid_price'));
        }

        return $this->createInvoice(
            $wallet,
            $minutes,
            $pricePerHour,
            $clientMessage
        );
    }

    /**
     * Mark an invoice as paid and add credit to wallet.
     *
     * This is the ONLY way an invoice affects the ledger.
     * Invoices themselves are not the source of truth.
     *
     * @param Invoice $invoice
     * @return Invoice
     * @throws InvalidArgumentException
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        if (!$invoice->isOpen()) {
            throw new InvalidArgumentException(__('messages.invoice.cannot_modify'));
        }

        return DB::transaction(function () use ($invoice) {
            // Update invoice status
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
            ]);

            // Add credit transaction to the ledger
            $this->ledgerService->addCredit(
                $invoice->wallet,
                $invoice->minutes,
                __('messages.invoice.payment_description', ['invoice_id' => $invoice->id]),
                __('messages.invoice.payment_internal_note', ['invoice_id' => $invoice->id])
            );

            return $invoice->fresh();
        });
    }

    /**
     * Cancel an invoice.
     *
     * Cancelled invoices do not affect the ledger.
     *
     * @param Invoice $invoice
     * @return Invoice
     * @throws InvalidArgumentException
     */
    public function cancel(Invoice $invoice): Invoice
    {
        if (!$invoice->isOpen()) {
            throw new InvalidArgumentException(__('messages.invoice.cannot_modify'));
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => Invoice::STATUS_CANCELLED,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Create an invoice.
     *
     * @param Wallet $wallet
     * @param int $minutes
     * @param float $pricePerHour
     * @param string|null $clientMessage
     * @return Invoice
     */
    private function createInvoice(
        Wallet $wallet,
        int $minutes,
        float $pricePerHour,
        ?string $clientMessage
    ): Invoice {
        $totalAmount = $this->calculateTotalAmount($minutes, $pricePerHour);

        return DB::transaction(fn () => Invoice::create([
            'wallet_id' => $wallet->id,
            'status' => Invoice::STATUS_OPEN,
            'minutes' => $minutes,
            'price_per_hour' => $pricePerHour,
            'total_amount' => $totalAmount,
            'client_message' => $clientMessage,
        ]));
    }

    /**
     * Calculate total amount based on minutes and price per hour.
     *
     * @param int $minutes
     * @param float $pricePerHour
     * @return float
     */
    private function calculateTotalAmount(int $minutes, float $pricePerHour): float
    {
        $hours = $minutes / 60;

        return round($hours * $pricePerHour, 2);
    }
}
