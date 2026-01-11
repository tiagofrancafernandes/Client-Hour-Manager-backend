<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $client = Client::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            // Create default wallet
            Wallet::create([
                'client_id' => $client->id,
                'name' => __('messages.wallet.default_wallet_name'),
                'is_default' => true,
            ]);

            return $client->fresh(['wallets']);
        });
    }

    public function update(Client $client, array $data): Client
    {
        $client->update([
            'name' => $data['name'] ?? $client->name,
            'email' => $data['email'] ?? $client->email,
            'phone' => $data['phone'] ?? $client->phone,
            'status' => $data['status'] ?? $client->status,
        ]);

        return $client->fresh();
    }

    public function delete(Client $client): bool
    {
        // Soft delete
        return $client->delete();
    }

    public function restore(int $clientId): ?Client
    {
        $client = Client::withTrashed()->find($clientId);

        if (! $client) {
            return null;
        }

        $client->restore();

        return $client->fresh();
    }
}
