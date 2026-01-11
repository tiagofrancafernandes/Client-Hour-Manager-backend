<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Wallet;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->with('client');

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min($request->input('per_page', 15), 100);
        $invoices = $query->latest('issued_at')->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:wallets,id'],
            'minutes' => ['required', 'integer', 'min:1'],
            'price_per_hour' => ['required', 'integer', 'min:1'],
            'client_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $wallet = Wallet::findOrFail($validated['wallet_id']);

        $invoice = $this->invoiceService->createForPackage(
            $wallet,
            $validated['minutes'],
            $validated['price_per_hour'],
            $validated['client_message'] ?? null
        );

        return response()->json([
            'message' => __('messages.invoice.created'),
            'data' => new InvoiceResource($invoice),
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load('client');

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function markAsPaid(Invoice $invoice): JsonResponse
    {
        $this->authorize('markAsPaid', $invoice);

        $invoice = $this->invoiceService->markAsPaid($invoice);

        return response()->json([
            'message' => __('messages.invoice.marked_as_paid'),
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'open') {
            return response()->json([
                'message' => __('messages.invoice.cannot_cancel_paid'),
            ], 422);
        }

        $invoice->update(['status' => 'cancelled']);

        return response()->json([
            'message' => __('messages.invoice.cancelled'),
            'data' => new InvoiceResource($invoice),
        ]);
    }
}
