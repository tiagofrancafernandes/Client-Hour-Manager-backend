<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'client']);

        // Create permissions
        Permission::create(['name' => 'manage wallets']);
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'active',
                    'roles',
                    'permissions',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals($user->id, $response->json('user.id'));
        $this->assertEquals($user->email, $response->json('user.email'));
    }

    public function testLoginWithInvalidCredentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => __('auth.failed'),
            ]);
    }

    public function testLoginWithInactiveAccount(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => __('auth.inactive_account'),
            ]);
    }

    public function testLoginValidationFailsWhenEmailMissing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function testLoginValidationFailsWhenPasswordMissing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function testLoginValidationFailsWhenEmailInvalid(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function testInfoReturnsUserDataWhenAuthenticated(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole('admin');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'active',
                    'roles',
                    'permissions',
                    'created_at',
                ],
            ]);

        $this->assertEquals($user->id, $response->json('user.id'));
        $this->assertContains('admin', $response->json('user.roles'));
    }

    public function testInfoReturnsUnauthenticatedWhenNoToken(): void
    {
        $response = $this->getJson('/api/v1/auth/info');

        $response->assertStatus(401)
            ->assertJson([
                'message' => __('auth.unauthenticated'),
            ]);
    }

    public function testInfoReturnsUnauthenticatedWhenInvalidToken(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/auth/info');

        $response->assertStatus(401);
    }

    public function testLogoutSuccessfully(): void
    {
        $user = User::factory()->create(['active' => true]);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('auth.logged_out'),
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function testLogoutRequiresAuthentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => __('auth.unauthenticated'),
            ]);
    }

    public function testUserResourceIncludesRolesAndPermissions(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole('admin');
        $user->givePermissionTo('manage wallets');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/info');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'roles' => ['admin'],
            ]);

        $this->assertContains('manage wallets', $response->json('user.permissions'));
    }
}
