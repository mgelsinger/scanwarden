<?php

namespace Tests\Feature;

use App\Models\BattleMatch;
use App\Models\Sector;
use App\Models\SectorTower;
use App\Models\SectorTowerStage;
use App\Models\Team;
use App\Models\User;
use App\Models\UserTowerProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TowerFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Sector $sector;
    private SectorTower $tower;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->seed(\Database\Seeders\SectorSeeder::class);
        $this->sector = Sector::where('name', 'Tech Sector')->first();
        $this->user = User::factory()->create();

        // Create a test tower
        $this->tower = SectorTower::create([
            'sector_id' => $this->sector->id,
            'slug' => 'tech-tower',
            'name' => 'Tech Spire',
            'description' => 'Test tower',
            'max_floor' => 5,
            'is_active' => true,
        ]);

        // Create a team for the user with a unit
        $this->team = Team::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Team',
        ]);

        // Create a unit for the team
        $unit = $this->user->summonedUnits()->create([
            'name' => 'Test Unit',
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

        $this->team->units()->attach($unit->id, ['position' => 1]);
    }

    public function test_towers_index_visible_to_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('towers.index'));

        $response->assertStatus(200);
        $response->assertViewIs('towers.index');
        $response->assertViewHas('towers');
        $response->assertSee($this->tower->name);
    }

    public function test_guest_cannot_access_towers_index(): void
    {
        $response = $this->get(route('towers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_tower_show_displays_floors_and_status(): void
    {
        // Create stages for the tower
        SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'common',
                    'tier' => 1,
                    'base_hp' => 60,
                    'base_attack' => 25,
                    'base_defense' => 15,
                    'base_speed' => 20,
                    'passive_key' => null,
                ],
            ],
            'recommended_power' => 50,
            'rewards' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
            'is_active' => true,
        ]);

        SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 2,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'uncommon',
                    'tier' => 1,
                    'base_hp' => 78,
                    'base_attack' => 32,
                    'base_defense' => 19,
                    'base_speed' => 26,
                    'passive_key' => 'tech_overclock',
                ],
            ],
            'recommended_power' => 65,
            'rewards' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 60],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('towers.show', $this->tower));

        $response->assertStatus(200);
        $response->assertViewIs('towers.show');
        $response->assertViewHas(['tower', 'progress', 'stages', 'teams']);
        $response->assertSee('Floor 1');
        $response->assertSee('Floor 2');
    }

    public function test_guest_cannot_access_tower_show(): void
    {
        $response = $this->get(route('towers.show', $this->tower));

        $response->assertRedirect(route('login'));
    }

    public function test_cannot_attempt_locked_floor(): void
    {
        // Create floor 1 and floor 3 (skipping floor 2)
        SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'common',
                    'tier' => 1,
                    'base_hp' => 60,
                    'base_attack' => 25,
                    'base_defense' => 15,
                    'base_speed' => 20,
                    'passive_key' => null,
                ],
            ],
            'recommended_power' => 50,
            'rewards' => [],
            'is_active' => true,
        ]);

        $stage3 = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 3,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'rare',
                    'tier' => 1,
                    'base_hp' => 100,
                    'base_attack' => 40,
                    'base_defense' => 25,
                    'base_speed' => 30,
                    'passive_key' => 'tech_overclock',
                ],
            ],
            'recommended_power' => 150,
            'rewards' => [],
            'is_active' => true,
        ]);

        // User has not cleared any floors yet
        $response = $this->actingAs($this->user)->post(
            route('towers.fight', ['tower' => $this->tower, 'floor' => 3]),
            ['team_id' => $this->team->id]
        );

        $response->assertRedirect(route('towers.show', $this->tower));
        $response->assertSessionHas('error');
    }

    public function test_successful_tower_battle_updates_progress(): void
    {
        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'common',
                    'tier' => 1,
                    'base_hp' => 1, // Very weak enemy to ensure victory
                    'base_attack' => 1,
                    'base_defense' => 1,
                    'base_speed' => 1,
                    'passive_key' => null,
                ],
            ],
            'recommended_power' => 10,
            'rewards' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('towers.fight', ['tower' => $this->tower, 'floor' => 1]),
            ['team_id' => $this->team->id]
        );

        $response->assertRedirect(route('towers.show', $this->tower));

        // Verify progress was updated
        $progress = UserTowerProgress::where('user_id', $this->user->id)
            ->where('tower_id', $this->tower->id)
            ->first();

        $this->assertNotNull($progress);
        $this->assertEquals(1, $progress->highest_floor_cleared);
        $this->assertNotNull($progress->last_attempt_at);
    }

    public function test_tower_battles_do_not_affect_rating(): void
    {
        // Refresh user to get the current rating (should be 1000 from migration default)
        $this->user->refresh();
        $initialRating = $this->user->rating;

        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'common',
                    'tier' => 1,
                    'base_hp' => 1,
                    'base_attack' => 1,
                    'base_defense' => 1,
                    'base_speed' => 1,
                    'passive_key' => null,
                ],
            ],
            'recommended_power' => 10,
            'rewards' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('towers.fight', ['tower' => $this->tower, 'floor' => 1]),
            ['team_id' => $this->team->id]
        );

        // Verify user rating did not change
        $this->user->refresh();
        $this->assertEquals($initialRating, $this->user->rating);

        // Verify battle match was created with null ratings (no rating impact)
        $battleMatch = BattleMatch::where('user_id', $this->user->id)
            ->latest()
            ->first();

        $this->assertNotNull($battleMatch);
        $this->assertEquals($this->user->id, $battleMatch->attacker_id);
        $this->assertNull($battleMatch->defender_id);
        $this->assertNull($battleMatch->attacker_rating_before);
        $this->assertNull($battleMatch->attacker_rating_after);
        $this->assertNull($battleMatch->defender_rating_before);
        $this->assertNull($battleMatch->defender_rating_after);
        $this->assertEquals(0, $battleMatch->rating_change);
    }

    public function test_cannot_fight_with_empty_team(): void
    {
        // Create an empty team
        $emptyTeam = Team::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Empty Team',
        ]);

        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'common',
                    'tier' => 1,
                    'base_hp' => 60,
                    'base_attack' => 25,
                    'base_defense' => 15,
                    'base_speed' => 20,
                    'passive_key' => null,
                ],
            ],
            'recommended_power' => 50,
            'rewards' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('towers.fight', ['tower' => $this->tower, 'floor' => 1]),
            ['team_id' => $emptyTeam->id]
        );

        $response->assertRedirect(route('towers.show', $this->tower));
        $response->assertSessionHas('error');
    }
}
