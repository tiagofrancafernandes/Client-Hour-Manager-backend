<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Client;
use App\Models\Wallet;
use App\Services\HourBalanceService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly HourBalanceService $balanceService
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Wallet::class);

        $query = Wallet::query()->with(['client']);

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Include archived
        if (! $request->boolean('include_archived')) {
            $query->whereNull('archived_at');
        }

        $perPage = min($request->input('per_page', 15), 100);
        $wallets = $query->paginate($perPage);

        return WalletResource::collection($wallets);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Wallet::class);

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'allow_client_purchases' => ['nullable', 'boolean'],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $wallet = $this->walletService->create($client, $validated);

        return response()->json([
            'message' => __('messages.wallet.created'),
            'data' => new WalletResource($wallet),
        ], 201);
    }

    public function show(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        $wallet->load(['client', 'packages']);

        // Add balance
        $balance = $this->balanceService->getBalanceInMinutes($wallet);
        $wallet->balance_minutes = $balance;
        $wallet->balance_formatted = $this->balanceService->formatMinutesToHours($balance);

        return response()->json([
            'data' => new WalletResource($wallet),
        ]);
    }

    public function update(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'allow_client_purchases' => ['sometimes', 'boolean'],
        ]);

        $wallet = $this->walletService->update($wallet, $validated);

        return response()->json([
            'message' => __('messages.wallet.updated'),
            'data' => new WalletResource($wallet),
        ]);
    }

    public function archive(Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $wallet = $this->walletService->archive($wallet);

        return response()->json([
            'message' => __('messages.wallet.archived'),
            'data' => new WalletResource($wallet),
        ]);
    }

    public function unarchive(Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $wallet = $this->walletService->unarchive($wallet);

        return response()->json([
            'message' => __('messages.wallet.unarchived'),
            'data' => new WalletResource($wallet),
        ]);
    }
}
