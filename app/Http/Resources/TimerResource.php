<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'started_by_id' => $this->started_by_id,
            'status' => $this->status,
            'is_hidden' => $this->is_hidden,
            'minutes' => $this->minutes,
            'description' => $this->description,
            'started_at' => $this->started_at?->toISOString(),
            'paused_at' => $this->paused_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed
            'current_minutes' => $this->when(
                $this->status === 'running' || $this->status === 'paused',
                fn () => $this->getCurrentMinutes()
            ),

            // Relationships
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'started_by' => new UserResource($this->whenLoaded('startedBy')),
        ];
    }
}
