<?php

namespace Tests\Feature\Api;

use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private string $tokenA;
    private string $tokenB;
    private Sector $sector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SectorSeeder::class);
        $this->sector = Sector::first();

        // Create User A and token
        $this->userA = User::factory()->create();
        $this->tokenA = $this->userA->createToken('mobile-client', ['mobile'])->plainTextToken;

        // Create User B and token
        $this->userB = User::factory()->create();
        $this->tokenB = $this->userB->createToken('mobile-client', ['mobile'])->plainTextToken;
    }

    public function test_cannot_access_another_users_unit(): void
    {
        // Create unit for User A
        $unitA = $this->userA->summonedUnits()->create([
            'name' => 'User A Unit',
            'sector_id' => $this->sector->id,
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 100,
            'current_hp' => 100,
            'max_hp' => 100,
            'attack' => 30,
            'defense' => 20,
            'speed' => 25,
        ]);

        // Try to access User A's unit as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson("/api/units/{$unitA->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden',
            'code' => 'forbidden',
        ]);
    }

    public function test_cannot_access_another_users_team(): void
    {
        // Create team for User A
        $teamA = $this->userA->teams()->create([
            'name' => 'User A Team',
        ]);

        // Try to access User A's team as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson("/api/teams/{$teamA->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden',
            'code' => 'forbidden',
        ]);
    }

    public function test_cannot_modify_another_users_team(): void
    {
        // Create team for User A
        $teamA = $this->userA->teams()->create([
            'name' => 'User A Team',
        ]);

        // Try to update User A's team as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->putJson("/api/teams/{$teamA->id}", [
            'name' => 'Hacked Team Name',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden',
            'code' => 'forbidden',
        ]);

        // Verify team name was not changed
        $this->assertEquals('User A Team', $teamA->fresh()->name);
    }

    public function test_cannot_delete_another_users_team(): void
    {
        // Create team for User A
        $teamA = $this->userA->teams()->create([
            'name' => 'User A Team',
        ]);

        // Try to delete User A's team as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->deleteJson("/api/teams/{$teamA->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden',
            'code' => 'forbidden',
        ]);

        // Verify team still exists
        $this->assertDatabaseHas('teams', ['id' => $teamA->id]);
    }

    public function test_cannot_add_unit_to_another_users_team(): void
    {
        // Create team for User A
        $teamA = $this->userA->teams()->create([
            'name' => 'User A Team',
        ]);

        // Create unit for User B
        $unitB = $this->userB->summonedUnits()->create([
            'name' => 'User B Unit',
            'sector_id' => $this->sector->id,
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 100,
            'current_hp' => 100,
            'max_hp' => 100,
            'attack' => 30,
            'defense' => 20,
            'speed' => 25,
        ]);

        // Try to add User B's unit to User A's team as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->postJson("/api/teams/{$teamA->id}/units", [
            'unit_id' => $unitB->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden',
            'code' => 'forbidden',
        ]);
    }

    public function test_teams_index_returns_only_own_teams(): void
    {
        // User A creates 2 teams
        $this->userA->teams()->createMany([
            ['name' => 'Team A1'],
            ['name' => 'Team A2'],
        ]);

        // User B creates 1 team
        $teamB = $this->userB->teams()->create([
            'name' => 'Team B1',
        ]);

        // Get teams as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/teams');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Team B1');
    }

    public function test_units_index_returns_only_own_units(): void
    {
        // User A creates 2 units
        $this->userA->summonedUnits()->createMany([
            [
                'name' => 'Unit A1',
                'sector_id' => $this->sector->id,
                'rarity' => 'rare',
                'tier' => 1,
                'hp' => 100,
                'current_hp' => 100,
                'max_hp' => 100,
                'attack' => 30,
                'defense' => 20,
                'speed' => 25,
            ],
            [
                'name' => 'Unit A2',
                'sector_id' => $this->sector->id,
                'rarity' => 'epic',
                'tier' => 1,
                'hp' => 120,
                'current_hp' => 120,
                'max_hp' => 120,
                'attack' => 35,
                'defense' => 25,
                'speed' => 30,
            ],
        ]);

        // User B creates 1 unit
        $this->userB->summonedUnits()->create([
            'name' => 'Unit B1',
            'sector_id' => $this->sector->id,
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 100,
            'current_hp' => 100,
            'max_hp' => 100,
            'attack' => 30,
            'defense' => 20,
            'speed' => 25,
        ]);

        // Get units as User B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/units');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Unit B1');
    }

    public function test_cannot_add_another_users_unit_to_own_team(): void
    {
        // Create team for User A
        $teamA = $this->userA->teams()->create([
            'name' => 'User A Team',
        ]);

        // Create unit for User B
        $unitB = $this->userB->summonedUnits()->create([
            'name' => 'User B Unit',
            'sector_id' => $this->sector->id,
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 100,
            'current_hp' => 100,
            'max_hp' => 100,
            'attack' => 30,
            'defense' => 20,
            'speed' => 25,
        ]);

        // Try to add User B's unit to User A's team as User A
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/teams/{$teamA->id}/units", [
            'unit_id' => $unitB->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden',
            'code' => 'forbidden',
        ]);

        // Verify unit was not added to team
        $this->assertEquals(0, $teamA->units()->count());
    }
}
