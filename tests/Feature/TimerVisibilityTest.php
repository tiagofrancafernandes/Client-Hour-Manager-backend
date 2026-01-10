<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Timer;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'client']);
    }

    /**
     * Test admin sees all timers.
     */
    public function test_admin_sees_all_timers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $wallet1 = Wallet::factory()->create(['client_id' => $client1->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client2->id]);

        // Create timers: some hidden, some not
        Timer::factory()->create([
            'wallet_id' => $wallet1->id,
            'created_by' => $client1->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet1->id,
            'created_by' => $client1->id,
            'is_hidden' => true,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet2->id,
            'created_by' => $client2->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet2->id,
            'created_by' => $client2->id,
            'is_hidden' => true,
        ]);

        $visibleTimers = Timer::visibleTo($admin)->get();

        // Admin should see all 4 timers
        $this->assertCount(4, $visibleTimers);
    }

    /**
     * Test creator sees their own hidden timers.
     */
    public function test_creator_sees_own_hidden_timers(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        // Create timers by this client
        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'is_hidden' => true,
        ]);

        $visibleTimers = Timer::visibleTo($client)->get();

        // Client should see both their own timers (hidden and non-hidden)
        $this->assertCount(2, $visibleTimers);
    }

    /**
     * Test client does not see hidden timers from other clients.
     */
    public function test_client_does_not_see_others_hidden_timers(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $wallet1 = Wallet::factory()->create(['client_id' => $client1->id]);
        $wallet2 = Wallet::factory()->create(['client_id' => $client2->id]);

        // Client1's timers
        Timer::factory()->create([
            'wallet_id' => $wallet1->id,
            'created_by' => $client1->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet1->id,
            'created_by' => $client1->id,
            'is_hidden' => true,
        ]);

        // Client2's timers
        Timer::factory()->create([
            'wallet_id' => $wallet2->id,
            'created_by' => $client2->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet2->id,
            'created_by' => $client2->id,
            'is_hidden' => true,
        ]);

        $visibleToClient1 = Timer::visibleTo($client1)->get();

        // Client1 should see:
        // - Their own 2 timers (1 hidden + 1 non-hidden)
        // - Client2's 1 non-hidden timer
        // Total: 3 timers
        $this->assertCount(3, $visibleToClient1);

        // Verify client1 sees their hidden timer
        $this->assertTrue(
            $visibleToClient1->contains(fn ($timer) => $timer->created_by === $client1->id && $timer->is_hidden)
        );

        // Verify client1 does NOT see client2's hidden timer
        $this->assertFalse(
            $visibleToClient1->contains(fn ($timer) => $timer->created_by === $client2->id && $timer->is_hidden)
        );
    }

    /**
     * Test client sees only non-hidden timers from others.
     */
    public function test_client_sees_only_non_hidden_timers_except_own(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $wallet = Wallet::factory()->create(['client_id' => $client1->id]);

        // Create non-hidden timer by other client
        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client2->id,
            'is_hidden' => false,
        ]);

        // Create hidden timer by other client
        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client2->id,
            'is_hidden' => true,
        ]);

        $visibleTimers = Timer::visibleTo($client1)->get();

        // Client1 should see only the non-hidden timer from client2
        $this->assertCount(1, $visibleTimers);
        $this->assertFalse($visibleTimers->first()->is_hidden);
    }

    /**
     * Test notHidden scope returns only non-hidden timers.
     */
    public function test_not_hidden_scope(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'is_hidden' => true,
        ]);

        $notHiddenTimers = Timer::notHidden()->get();

        $this->assertCount(1, $notHiddenTimers);
        $this->assertFalse($notHiddenTimers->first()->is_hidden);
    }

    /**
     * Test hidden scope returns only hidden timers.
     */
    public function test_hidden_scope(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'is_hidden' => false,
        ]);

        Timer::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $client->id,
            'is_hidden' => true,
        ]);

        $hiddenTimers = Timer::hidden()->get();

        $this->assertCount(1, $hiddenTimers);
        $this->assertTrue($hiddenTimers->first()->is_hidden);
    }
}
