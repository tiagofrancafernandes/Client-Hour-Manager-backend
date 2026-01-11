<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'allow_client_purchases' => $this->allow_client_purchases,
            'archived_at' => $this->archived_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Balance (when requested)
            'balance_minutes' => $this->when(isset($this->balance_minutes), $this->balance_minutes),
            'balance_formatted' => $this->when(isset($this->balance_formatted), $this->balance_formatted),

            // Relationships
            'client' => new ClientResource($this->whenLoaded('client')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'packages' => WalletPackageResource::collection($this->whenLoaded('packages')),
        ];
    }
}
