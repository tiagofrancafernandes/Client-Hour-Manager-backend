<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class WalletService
{
    public function create(Client $client, array $data): Wallet
    {
        $wallet = Wallet::create([
            'client_id' => $client->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'allow_client_purchases' => $data['allow_client_purchases'] ?? false,
        ]);

        return $wallet->fresh();
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        $wallet->update([
            'name' => $data['name'] ?? $wallet->name,
            'description' => $data['description'] ?? $wallet->description,
            'allow_client_purchases' => $data['allow_client_purchases'] ?? $wallet->allow_client_purchases,
        ]);

        return $wallet->fresh();
    }

    public function archive(Wallet $wallet): Wallet
    {
        if ($wallet->is_default) {
            throw new \DomainException(__('messages.wallet.cannot_archive_default'));
        }

        $wallet->update(['archived_at' => now()]);

        return $wallet->fresh();
    }

    public function unarchive(Wallet $wallet): Wallet
    {
        $wallet->update(['archived_at' => null]);

        return $wallet->fresh();
    }

    public function getAllForClient(Client $client, bool $includeArchived = false): Collection
    {
        $query = $client->wallets();

        if (! $includeArchived) {
            $query->whereNull('archived_at');
        }

        return $query->get();
    }
}
