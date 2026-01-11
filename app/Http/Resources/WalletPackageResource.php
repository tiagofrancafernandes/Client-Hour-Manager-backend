<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'name' => $this->name,
            'description' => $this->description,
            'minutes' => $this->minutes,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
        ];
    }
}
