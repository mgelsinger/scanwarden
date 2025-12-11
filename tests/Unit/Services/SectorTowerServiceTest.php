<?php

namespace Tests\Unit\Services;

use App\Models\Sector;
use App\Models\SectorTower;
use App\Models\SectorTowerStage;
use App\Models\User;
use App\Models\UserTowerProgress;
use App\Services\Battle\BattleResult;
use App\Services\ResourceService;
use App\Services\SectorTowerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectorTowerServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectorTowerService $service;
    private User $user;
    private Sector $sector;
    private SectorTower $tower;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SectorSeeder::class);
        $this->sector = Sector::where('name', 'Tech Sector')->first();
        $this->user = User::factory()->create();

        $this->service = new SectorTowerService(
            app(ResourceService::class)
        );

        // Create a test tower
        $this->tower = SectorTower::create([
            'sector_id' => $this->sector->id,
            'slug' => 'tech-tower',
            'name' => 'Tech Spire',
            'description' => 'Test tower',
            'max_floor' => 5,
            'is_active' => true,
        ]);
    }

    public function test_get_or_create_progress_creates_new_progress(): void
    {
        $progress = $this->service->getOrCreateProgress($this->user, $this->tower);

        $this->assertInstanceOf(UserTowerProgress::class, $progress);
        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->tower->id, $progress->tower_id);
        $this->assertEquals(0, $progress->highest_floor_cleared);
    }

    public function test_get_or_create_progress_retrieves_existing_progress(): void
    {
        // Create existing progress
        $existing = UserTowerProgress::create([
            'user_id' => $this->user->id,
            'tower_id' => $this->tower->id,
            'highest_floor_cleared' => 3,
        ]);

        $progress = $this->service->getOrCreateProgress($this->user, $this->tower);

        $this->assertEquals($existing->id, $progress->id);
        $this->assertEquals(3, $progress->highest_floor_cleared);
    }

    public function test_can_attempt_floor_allows_floor_one(): void
    {
        $progress = $this->service->getOrCreateProgress($this->user, $this->tower);

        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [],
            'recommended_power' => 50,
            'rewards' => [],
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->canAttemptFloor($progress, $stage));
    }

    public function test_can_attempt_floor_denies_locked_floor(): void
    {
        $progress = $this->service->getOrCreateProgress($this->user, $this->tower);

        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 3,
            'enemy_team' => [],
            'recommended_power' => 150,
            'rewards' => [],
            'is_active' => true,
        ]);

        $this->assertFalse($this->service->canAttemptFloor($progress, $stage));
    }

    public function test_can_attempt_floor_allows_next_floor_after_clear(): void
    {
        $progress = $this->service->getOrCreateProgress($this->user, $this->tower);
        $progress->highest_floor_cleared = 2;
        $progress->save();

        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 3,
            'enemy_team' => [],
            'recommended_power' => 150,
            'rewards' => [],
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->canAttemptFloor($progress, $stage));
    }

    public function test_build_enemy_team_from_stage(): void
    {
        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [
                [
                    'slot' => 1,
                    'sector_id' => $this->sector->id,
                    'rarity' => 'rare',
                    'tier' => 1,
                    'base_hp' => 100,
                    'base_attack' => 30,
                    'base_defense' => 20,
                    'base_speed' => 25,
                    'passive_key' => 'tech_overclock',
                ],
            ],
            'recommended_power' => 50,
            'rewards' => [],
            'is_active' => true,
        ]);

        $enemyTeam = $this->service->buildEnemyTeamFromStage($stage);

        $this->assertCount(1, $enemyTeam);
        $this->assertEquals(1, $enemyTeam[0]['id']);
        $this->assertEquals(100, $enemyTeam[0]['hp']);
        $this->assertEquals(30, $enemyTeam[0]['attack']);
        $this->assertEquals(20, $enemyTeam[0]['defense']);
        $this->assertEquals(25, $enemyTeam[0]['speed']);
        $this->assertEquals('rare', $enemyTeam[0]['rarity']);
        $this->assertEquals('tech_overclock', $enemyTeam[0]['passive_key']);
        $this->assertInstanceOf(Sector::class, $enemyTeam[0]['sector']);
    }

    public function test_handle_battle_result_on_first_clear_victory(): void
    {
        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [],
            'recommended_power' => 50,
            'rewards' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
            'is_active' => true,
        ]);

        $battleResult = new BattleResult(
            outcome: 'attacker_win',
            winnerUserId: $this->user->id,
            turns: [],
            finalStates: [],
            totalTurns: 5,
            attackerSurvivors: 1,
            defenderSurvivors: 0
        );

        $result = $this->service->handleBattleResult($this->user, $this->tower, $stage, $battleResult);

        $this->assertTrue($result['did_win']);
        $this->assertTrue($result['first_clear']);
        $this->assertEquals(1, $result['new_highest_floor_cleared']);
        $this->assertNotEmpty($result['rewards_granted']);

        // Verify progress was updated
        $progress = UserTowerProgress::where('user_id', $this->user->id)
            ->where('tower_id', $this->tower->id)
            ->first();
        $this->assertEquals(1, $progress->highest_floor_cleared);
        $this->assertNotNull($progress->last_attempt_at);
    }

    public function test_handle_battle_result_on_defeat(): void
    {
        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [],
            'recommended_power' => 50,
            'rewards' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
            'is_active' => true,
        ]);

        $battleResult = new BattleResult(
            outcome: 'defender_win',
            winnerUserId: null,
            turns: [],
            finalStates: [],
            totalTurns: 10,
            attackerSurvivors: 0,
            defenderSurvivors: 1
        );

        $result = $this->service->handleBattleResult($this->user, $this->tower, $stage, $battleResult);

        $this->assertFalse($result['did_win']);
        $this->assertFalse($result['first_clear']);
        $this->assertEquals(0, $result['new_highest_floor_cleared']);
        $this->assertEmpty($result['rewards_granted']);

        // Verify progress was not updated
        $progress = UserTowerProgress::where('user_id', $this->user->id)
            ->where('tower_id', $this->tower->id)
            ->first();
        $this->assertEquals(0, $progress->highest_floor_cleared);
    }

    public function test_handle_battle_result_no_rewards_on_re_clear(): void
    {
        // Set initial progress
        $progress = $this->service->getOrCreateProgress($this->user, $this->tower);
        $progress->highest_floor_cleared = 2;
        $progress->save();

        $stage = SectorTowerStage::create([
            'tower_id' => $this->tower->id,
            'floor' => 1,
            'enemy_team' => [],
            'recommended_power' => 50,
            'rewards' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
            'is_active' => true,
        ]);

        $battleResult = new BattleResult(
            outcome: 'attacker_win',
            winnerUserId: $this->user->id,
            turns: [],
            finalStates: [],
            totalTurns: 5,
            attackerSurvivors: 1,
            defenderSurvivors: 0
        );

        $result = $this->service->handleBattleResult($this->user, $this->tower, $stage, $battleResult);

        $this->assertTrue($result['did_win']);
        $this->assertFalse($result['first_clear']); // Already cleared higher floor
        $this->assertEquals(2, $result['new_highest_floor_cleared']); // No change
        $this->assertEmpty($result['rewards_granted']); // No rewards

        // Verify progress was not changed
        $progress->refresh();
        $this->assertEquals(2, $progress->highest_floor_cleared);
    }
}
