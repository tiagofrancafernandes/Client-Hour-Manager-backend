<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WalletPolicyTest extends TestCase
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
     * Test admin can view any wallet.
     */
    public function testAdminCanViewAnyWallet(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $this->assertTrue($admin->can('view', $wallet));
    }

    /**
     * Test client can view own wallet.
     */
    public function testClientCanViewOwnWallet(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $this->assertTrue($client->can('view', $wallet));
    }

    /**
     * Test client cannot view other client's wallet.
     */
    public function testClientCannotViewOtherClientsWallet(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $wallet = Wallet::factory()->create(['client_id' => $client2->id]);

        $this->assertFalse($client1->can('view', $wallet));
    }

    /**
     * Test only admin can create wallets.
     */
    public function testOnlyAdminCanCreateWallets(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();

        $this->assertTrue($admin->can('create', Wallet::class));
        $this->assertFalse($client->can('create', Wallet::class));
    }

    /**
     * Test admin can update wallet.
     */
    public function testAdminCanUpdateWallet(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $this->assertTrue($admin->can('update', $wallet));
    }

    /**
     * Test client cannot update wallet.
     */
    public function testClientCannotUpdateWallet(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $this->assertFalse($client->can('update', $wallet));
    }

    /**
     * Test wallets cannot be deleted.
     */
    public function testWalletsCannotBeDeleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $this->assertFalse($admin->can('delete', $wallet));
        $this->assertFalse($client->can('delete', $wallet));
    }

    /**
     * Test wallets cannot be force deleted.
     */
    public function testWalletsCannotBeForceDeleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $this->assertFalse($admin->can('forceDelete', $wallet));
        $this->assertFalse($client->can('forceDelete', $wallet));
    }
}
