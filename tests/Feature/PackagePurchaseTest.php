<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Wallet;
use App\Models\WalletPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackagePurchaseTest extends TestCase
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
     * Test successful purchase initiation.
     */
    public function testCanInitiatePackagePurchase(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $wallet = Wallet::factory()->create([
            'client_id' => $client->id,
            'allow_client_purchases' => true,
        ]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet->id,
            'package_id' => $package->id,
            'message' => 'Need more hours for the project',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'package_id',
                'wallet_id',
                'wallet_name',
                'minutes',
                'price',
                'client_message',
            ],
        ]);

        $this->assertEquals($package->id, $response->json('data.package_id'));
        $this->assertEquals($wallet->id, $response->json('data.wallet_id'));
        $this->assertEquals(300, $response->json('data.minutes'));
        $this->assertEquals('150.00', $response->json('data.price'));
    }

    /**
     * Test purchase fails when wallet disables purchases.
     */
    public function testPurchaseFailsWhenWalletDisablesPurchases(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $wallet = Wallet::factory()->create([
            'client_id' => $client->id,
            'allow_client_purchases' => false,
        ]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet->id,
            'package_id' => $package->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['wallet_id']);
    }

    /**
     * Test purchase fails when package is inactive.
     */
    public function testPurchaseFailsWhenPackageIsInactive(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $wallet = Wallet::factory()->create([
            'client_id' => $client->id,
            'allow_client_purchases' => true,
        ]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'price' => 150.00,
            'is_active' => false,
        ]);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet->id,
            'package_id' => $package->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['package_id']);
    }

    /**
     * Test purchase fails when package doesn't belong to wallet.
     */
    public function testPurchaseFailsWhenPackageNotBelongToWallet(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $wallet1 = Wallet::factory()->create([
            'client_id' => $client->id,
            'allow_client_purchases' => true,
        ]);

        $wallet2 = Wallet::factory()->create([
            'client_id' => $client->id,
            'allow_client_purchases' => true,
        ]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet2->id,
            'minutes' => 300,
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet1->id,
            'package_id' => $package->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['package_id']);
    }

    /**
     * Test purchase requires authentication.
     */
    public function testPurchaseRequiresAuthentication(): void
    {
        $client = Client::factory()->create();
        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'price' => 150.00,
        ]);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet->id,
            'package_id' => $package->id,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test purchase with optional message.
     */
    public function testPurchasePersistsOptionalMessage(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $wallet = Wallet::factory()->create([
            'client_id' => $client->id,
            'allow_client_purchases' => true,
        ]);

        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'price' => 150.00,
            'is_active' => true,
        ]);

        $message = 'This is a test message for the purchase';

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet->id,
            'package_id' => $package->id,
            'message' => $message,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($message, $response->json('data.client_message'));
    }

    /**
     * Test validation error for missing wallet_id.
     */
    public function testValidationFailsWhenWalletIdMissing(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'package_id' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['wallet_id']);
    }

    /**
     * Test validation error for missing package_id.
     */
    public function testValidationFailsWhenPackageIdMissing(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['package_id']);
    }

    /**
     * Test validation error when message exceeds max length.
     */
    public function testValidationFailsWhenMessageTooLong(): void
    {
        $client = Client::factory()->create();
        Sanctum::actingAs($client);

        $wallet = Wallet::factory()->create(['client_id' => $client->id]);
        $package = WalletPackage::create([
            'wallet_id' => $wallet->id,
            'minutes' => 300,
            'price' => 150.00,
        ]);

        $response = $this->postJson(route('api.v1.packages.purchase'), [
            'wallet_id' => $wallet->id,
            'package_id' => $package->id,
            'message' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }
}
