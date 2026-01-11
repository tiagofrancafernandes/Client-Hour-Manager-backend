<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Wallet;
use App\Models\WalletPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPackageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a wallet package.
     */
    public function testCanCreateWalletPackage(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000, // 100 hours
            'price' => 500.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('wallet_packages', [
            'id' => $package->id,
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
            'is_active' => true,
        ]);
    }

    /**
     * Test wallet package belongs to wallet.
     */
    public function testWalletPackageBelongsToWallet(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
        ]);

        $this->assertInstanceOf(Wallet::class, $package->wallet);
        $this->assertEquals($wallet->id, $package->wallet->id);
    }

    /**
     * Test wallet has many packages.
     */
    public function testWalletHasManyPackages(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 3000,
            'price' => 250.00,
        ]);

        WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
        ]);

        $this->assertCount(2, $wallet->packages);
    }

    /**
     * Test active scope filters only active packages.
     */
    public function testActiveScopeReturnsOnlyActivePackages(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 3000,
            'price' => 250.00,
            'is_active' => true,
        ]);

        WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
            'is_active' => false,
        ]);

        $activePackages = WalletPackage::active()->get();

        $this->assertCount(1, $activePackages);
        $this->assertTrue($activePackages->first()->is_active);
    }

    /**
     * Test inactive packages are not returned by active scope.
     */
    public function testInactivePackagesNotReturnedByActiveScope(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
            'is_active' => false,
        ]);

        $activePackages = WalletPackage::active()->get();

        $this->assertCount(0, $activePackages);
    }

    /**
     * Test package is active by default.
     */
    public function testPackageIsActiveByDefault(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
        ]);

        $this->assertTrue($package->is_active);
    }

    /**
     * Test package cascade deletes when wallet is deleted.
     */
    public function testPackageCascadeDeletesWhenWalletDeleted(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
        ]);

        $wallet->delete();

        $this->assertDatabaseMissing('wallet_packages', [
            'id' => $package->id,
        ]);
    }

    /**
     * Test package price is stored as decimal.
     */
    public function testPackagePriceIsDecimal(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.50,
        ]);

        $this->assertEquals('500.50', $package->price);
    }

    /**
     * Test package minutes is integer.
     */
    public function testPackageMinutesIsInteger(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 6000,
            'price' => 500.00,
        ]);

        $this->assertIsInt($package->minutes);
    }
}
