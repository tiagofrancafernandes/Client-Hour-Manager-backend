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
    public function testCanAddCreditViaApi(): void
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
    public function testCanAddDebitViaApi(): void
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
    public function testValidationFailsWhenWalletIdMissing(): void
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
    public function testValidationFailsWhenMinutesMissing(): void
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
    public function testValidationFailsWhenMinutesIsZero(): void
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
    public function testValidationFailsWhenWalletNotFound(): void
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
    public function testUnauthorizedWithoutAuthentication(): void
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
    public function testCanAddCreditWithOccurredAt(): void
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
    public function testValidationFailsWhenDescriptionTooLong(): void
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
    public function testValidationFailsWhenInternalNoteTooLong(): void
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
