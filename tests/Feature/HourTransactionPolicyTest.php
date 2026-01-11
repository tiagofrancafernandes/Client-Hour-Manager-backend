<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HourTransactionPolicyTest extends TestCase
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
     * Test admin can view any transaction.
     */
    public function testAdminCanViewAnyTransaction(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $transaction = HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
        ]);

        $this->assertTrue($admin->can('view', $transaction));
    }

    /**
     * Test client can view own wallet's transactions.
     */
    public function testClientCanViewOwnWalletsTransactions(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $transaction = HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
        ]);

        $this->assertTrue($client->can('view', $transaction));
    }

    /**
     * Test client cannot view other client's transactions.
     */
    public function testClientCannotViewOtherClientsTransactions(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $wallet = Wallet::factory()->create(['client_id' => $client2->id]);

        $transaction = HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
        ]);

        $this->assertFalse($client1->can('view', $transaction));
    }

    /**
     * Test only admin can create transactions.
     */
    public function testOnlyAdminCanCreateTransactions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();

        $this->assertTrue($admin->can('create', HourTransaction::class));
        $this->assertFalse($client->can('create', HourTransaction::class));
    }

    /**
     * Test transactions cannot be updated.
     */
    public function testTransactionsCannotBeUpdated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $transaction = HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
        ]);

        $this->assertFalse($admin->can('update', $transaction));
        $this->assertFalse($client->can('update', $transaction));
    }

    /**
     * Test transactions cannot be deleted.
     */
    public function testTransactionsCannotBeDeleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $transaction = HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
        ]);

        $this->assertFalse($admin->can('delete', $transaction));
        $this->assertFalse($client->can('delete', $transaction));
    }

    /**
     * Test only admin can view internal notes.
     */
    public function testOnlyAdminCanViewInternalNotes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $transaction = HourTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => HourTransaction::TYPE_CREDIT,
            'minutes' => 100,
            'internal_note' => 'Admin only note',
        ]);

        $this->assertTrue($admin->can('viewInternalNote', $transaction));
        $this->assertFalse($client->can('viewInternalNote', $transaction));
    }
}
