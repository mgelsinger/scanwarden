<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SectorSeeder::class);

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('mobile-client', ['mobile'])->plainTextToken;
    }

    public function test_scan_rate_limit_is_enforced(): void
    {
        // The scan endpoint has a stricter rate limit (10/min) than the general API (60/min)
        // We'll make rapid-fire requests until we hit the rate limit
        // Note: we need unique UPCs for each scan
        $successfulScans = 0;
        $hitRateLimit = false;

        for ($i = 1; $i <= 12; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson('/api/scan', [
                'upc' => str_pad((string)$i, 12, '0', STR_PAD_LEFT), // e.g., 000000000001, 000000000002, etc.
            ]);

            if ($response->status() === 201) {
                $successfulScans++;
            } elseif ($response->status() === 429) {
                $hitRateLimit = true;
                $response->assertJson([
                    'message' => 'Too Many Requests.',
                    'code' => 'too_many_requests',
                ]);
                break;
            }
        }

        // We should have hit the rate limit and had at most 10 successful scans
        $this->assertTrue($hitRateLimit, 'Rate limit was not enforced');
        $this->assertLessThanOrEqual(10, $successfulScans, 'More than 10 scans succeeded before rate limit');
    }

    public function test_general_api_rate_limit_allows_60_requests(): void
    {
        // Test that general API endpoints allow up to 60 requests per minute
        // We'll use the /api/teams endpoint as it's a simple read operation

        for ($i = 1; $i <= 60; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->getJson('/api/teams');

            $response->assertStatus(200);
        }

        // 61st request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/teams');

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too Many Requests.',
            'code' => 'too_many_requests',
        ]);
    }
}
