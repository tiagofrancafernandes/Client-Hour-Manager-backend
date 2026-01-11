<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()->with('wallets');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $perPage = min($request->input('per_page', 15), 100);
        $clients = $query->paginate($perPage);

        return ClientResource::collection($clients);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return response()->json([
            'message' => __('messages.client.created'),
            'data' => new ClientResource($client),
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $client->load('wallets');

        return response()->json([
            'data' => new ClientResource($client),
        ]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:clients,email,' . $client->id],
            'phone' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        $client = $this->clientService->update($client, $validated);

        return response()->json([
            'message' => __('messages.client.updated'),
            'data' => new ClientResource($client),
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        $this->clientService->delete($client);

        return response()->json([
            'message' => __('messages.client.deleted'),
        ]);
    }
}
