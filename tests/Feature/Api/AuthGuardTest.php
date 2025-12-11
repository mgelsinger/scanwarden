<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SectorSeeder::class);
    }

    public function test_unauthenticated_access_to_units_is_blocked(): void
    {
        $response = $this->getJson('/api/units');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
            'code' => 'unauthenticated',
        ]);
    }

    public function test_unauthenticated_access_to_teams_is_blocked(): void
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
            'code' => 'unauthenticated',
        ]);
    }

    public function test_unauthenticated_access_to_scan_is_blocked(): void
    {
        $response = $this->postJson('/api/scan', [
            'upc' => '123456789012',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
            'code' => 'unauthenticated',
        ]);
    }

    public function test_unauthenticated_access_to_user_endpoint_is_blocked(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
            'code' => 'unauthenticated',
        ]);
    }

    public function test_token_with_mobile_ability_can_access_endpoints(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-client', ['mobile'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/units');

        $response->assertStatus(200);
    }

    public function test_register_creates_token_with_mobile_ability(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'user',
            'token',
        ]);

        // Use the token to access protected endpoint
        $token = $response->json('token');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
    }

    public function test_login_creates_token_with_mobile_ability(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user',
            'token',
        ]);

        // Use the token to access protected endpoint
        $token = $response->json('token');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = User::factory()->create();
        $tokenModel = $user->createToken('mobile-client', ['mobile']);
        $token = $tokenModel->plainTextToken;

        // Verify user has one token
        $this->assertEquals(1, $user->tokens()->count());

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logged out successfully',
        ]);

        // Verify token was deleted from database
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-12345',
        ])->getJson('/api/units');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
            'code' => 'unauthenticated',
        ]);
    }

    public function test_public_endpoints_do_not_require_authentication(): void
    {
        // Test register endpoint
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        // Test login endpoint
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }
}
