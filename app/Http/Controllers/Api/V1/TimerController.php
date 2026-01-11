<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimerResource;
use App\Models\Timer;
use App\Models\Wallet;
use App\Services\TimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimerController extends Controller
{
    public function __construct(
        private readonly TimerService $timerService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Timer::class);

        $query = Timer::query()->with(['wallet', 'startedBy']);

        // Filter by wallet
        if ($request->has('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Apply visibility scopes based on permissions
        if (! $request->user()->can('timer.view_hidden')) {
            $query->notHidden($request->user()->id);
        }

        $perPage = min($request->input('per_page', 15), 100);
        $timers = $query->latest()->paginate($perPage);

        return TimerResource::collection($timers);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Timer::class);

        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:wallets,id'],
            'is_hidden' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $wallet = Wallet::findOrFail($validated['wallet_id']);

        $timer = $this->timerService->start(
            $wallet,
            $request->user(),
            $validated['is_hidden'] ?? false,
            $validated['description'] ?? null
        );

        return response()->json([
            'message' => __('messages.timer.started'),
            'data' => new TimerResource($timer),
        ], 201);
    }

    public function show(Timer $timer): JsonResponse
    {
        $this->authorize('view', $timer);

        $timer->load(['wallet', 'startedBy']);

        return response()->json([
            'data' => new TimerResource($timer),
        ]);
    }

    public function pause(Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        $timer = $this->timerService->pause($timer);

        return response()->json([
            'message' => __('messages.timer.paused'),
            'data' => new TimerResource($timer),
        ]);
    }

    public function resume(Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        $timer = $this->timerService->resume($timer);

        return response()->json([
            'message' => __('messages.timer.resumed'),
            'data' => new TimerResource($timer),
        ]);
    }

    public function stop(Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        $timer = $this->timerService->stop($timer);

        return response()->json([
            'message' => __('messages.timer.stopped'),
            'data' => new TimerResource($timer->load('transaction')),
        ]);
    }

    public function cancel(Timer $timer): JsonResponse
    {
        $this->authorize('delete', $timer);

        $timer = $this->timerService->cancel($timer);

        return response()->json([
            'message' => __('messages.timer.cancelled'),
            'data' => new TimerResource($timer),
        ]);
    }
}
