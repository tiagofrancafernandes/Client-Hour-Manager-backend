<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test adding credit to wallet via API.
     */
    public function test_can_add_credit_via_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'description' => 'Test credit',
            'internal_note' => 'Admin note',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'wallet_id',
                'type',
                'minutes',
                'description',
                'occurred_at',
                'created_at',
            ],
        ]);

        $this->assertDatabaseHas('hour_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'minutes' => 300,
            'description' => 'Test credit',
            'internal_note' => 'Admin note',
        ]);
    }

    /**
     * Test adding debit to wallet via API.
     */
    public function test_can_add_debit_via_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.debit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 150,
            'description' => 'Test debit',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'wallet_id',
                'type',
                'minutes',
                'description',
                'occurred_at',
                'created_at',
            ],
        ]);

        $this->assertDatabaseHas('hour_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'minutes' => 150,
            'description' => 'Test debit',
        ]);
    }

    /**
     * Test validation error when wallet_id is missing.
     */
    public function test_validation_fails_when_wallet_id_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'minutes' => 300,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['wallet_id']);
    }

    /**
     * Test validation error when minutes is missing.
     */
    public function test_validation_fails_when_minutes_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['minutes']);
    }

    /**
     * Test validation error when minutes is zero.
     */
    public function test_validation_fails_when_minutes_is_zero(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['minutes']);
    }

    /**
     * Test validation error when wallet does not exist.
     */
    public function test_validation_fails_when_wallet_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => 99999,
            'minutes' => 300,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['wallet_id']);
    }

    /**
     * Test unauthorized access without authentication.
     */
    public function test_unauthorized_without_authentication(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 300,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test adding credit with optional occurred_at.
     */
    public function test_can_add_credit_with_occurred_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $occurredAt = now()->subDays(2);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'description' => 'Retroactive credit',
            'occurred_at' => $occurredAt->toDateTimeString(),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('hour_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'minutes' => 300,
        ]);
    }

    /**
     * Test description max length validation.
     */
    public function test_validation_fails_when_description_too_long(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'description' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    /**
     * Test internal note max length validation.
     */
    public function test_validation_fails_when_internal_note_too_long(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $response = $this->postJson(route('api.v1.transactions.credit'), [
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'internal_note' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['internal_note']);
    }
}
