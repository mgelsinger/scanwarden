<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\SummonedUnit;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnitApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $response = $this->getJson('/api/units');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_their_units(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();
        Sanctum::actingAs($user);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->getJson('/api/units');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'rarity', 'stats', 'sector']
                ]
            ]);
    }

    public function test_user_cannot_view_another_users_unit(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $sector = Sector::first();

        Sanctum::actingAs($user1);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user2->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->getJson("/api/units/{$unit->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_view_their_own_unit(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        Sanctum::actingAs($user);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->getJson("/api/units/{$unit->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'rarity', 'stats', 'sector']
            ])
            ->assertJson([
                'data' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ]
            ]);
    }
}
