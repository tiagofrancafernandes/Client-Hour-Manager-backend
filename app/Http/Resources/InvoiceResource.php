<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'minutes' => $this->minutes,
            'price_per_hour' => $this->price_per_hour,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'client_message' => $this->client_message,
            'issued_at' => $this->issued_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'client' => new ClientResource($this->whenLoaded('client')),
        ];
    }
}
