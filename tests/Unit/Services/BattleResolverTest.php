<?php

namespace Tests\Unit\Services;

use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\Team;
use App\Models\User;
use App\Services\BattleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleResolverTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected BattleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new BattleResolver();
    }

    public function test_faster_unit_acts_first(): void
    {
        // Create two units with different speeds
        $attackerUnits = [
            ['id' => 1, 'name' => 'Fast Unit', 'hp' => 100, 'attack' => 20, 'defense' => 10, 'speed' => 30],
        ];

        $defenderUnits = [
            ['id' => 2, 'name' => 'Slow Unit', 'hp' => 100, 'attack' => 20, 'defense' => 10, 'speed' => 10],
        ];

        $result = $this->resolver->resolveBattle($attackerUnits, $defenderUnits);

        // First turn should be the faster unit (attacker)
        $this->assertNotEmpty($result['turns']);
        $this->assertEquals('Fast Unit', $result['turns'][0]['attacker']);
        $this->assertEquals('attacker', $result['turns'][0]['attacker_team']);
    }

    public function test_high_attack_can_ko_low_defense(): void
    {
        // Attacker with very high attack vs defender with low defense and HP
        $attackerUnits = [
            ['id' => 1, 'name' => 'Strong Unit', 'hp' => 100, 'attack' => 100, 'defense' => 10, 'speed' => 20],
        ];

        $defenderUnits = [
            ['id' => 2, 'name' => 'Weak Unit', 'hp' => 10, 'attack' => 5, 'defense' => 5, 'speed' => 10],
        ];

        $result = $this->resolver->resolveBattle($attackerUnits, $defenderUnits);

        // Attacker should win quickly
        $this->assertEquals('attacker', $result['winner']);
        $this->assertLessThan(5, $result['total_turns']); // Should end very quickly
    }

    public function test_battle_ends_when_all_units_on_one_side_are_koed(): void
    {
        // Balanced teams but attacker slightly stronger
        $attackerUnits = [
            ['id' => 1, 'name' => 'Unit A', 'hp' => 100, 'attack' => 25, 'defense' => 15, 'speed' => 20],
        ];

        $defenderUnits = [
            ['id' => 2, 'name' => 'Unit B', 'hp' => 100, 'attack' => 20, 'defense' => 15, 'speed' => 15],
        ];

        $result = $this->resolver->resolveBattle($attackerUnits, $defenderUnits);

        // One side should win (all units on other side KO'd)
        $this->assertContains($result['winner'], ['attacker', 'defender', 'draw']);

        if ($result['winner'] === 'attacker') {
            $this->assertEquals(0, $result['defender_survivors']);
        } elseif ($result['winner'] === 'defender') {
            $this->assertEquals(0, $result['attacker_survivors']);
        }
    }

    public function test_draw_on_max_turn_limit(): void
    {
        // Two tanks with high HP and low attack - should timeout
        $attackerUnits = [
            ['id' => 1, 'name' => 'Tank A', 'hp' => 500, 'attack' => 1, 'defense' => 50, 'speed' => 20],
        ];

        $defenderUnits = [
            ['id' => 2, 'name' => 'Tank B', 'hp' => 500, 'attack' => 1, 'defense' => 50, 'speed' => 15],
        ];

        $result = $this->resolver->resolveBattle($attackerUnits, $defenderUnits);

        // Should reach max turns and determine winner by HP or draw
        $this->assertEquals(50, $result['total_turns']); // MAX_TURNS = 50
        $this->assertContains($result['winner'], ['attacker', 'defender', 'draw']);
    }

    public function test_damage_calculation_is_correct(): void
    {
        $attackerUnits = [
            ['id' => 1, 'name' => 'Attacker', 'hp' => 100, 'attack' => 30, 'defense' => 10, 'speed' => 20],
        ];

        $defenderUnits = [
            ['id' => 2, 'name' => 'Defender', 'hp' => 100, 'attack' => 20, 'defense' => 20, 'speed' => 10],
        ];

        $result = $this->resolver->resolveBattle($attackerUnits, $defenderUnits);

        // Check first turn damage
        // Expected: 30 - (20 * 0.5) = 30 - 10 = 20 damage
        $firstTurn = $result['turns'][0];
        $this->assertEquals(20, $firstTurn['damage']);
    }

    public function test_minimum_damage_is_one(): void
    {
        // Attacker with very low attack vs high defense
        $attackerUnits = [
            ['id' => 1, 'name' => 'Weak', 'hp' => 100, 'attack' => 5, 'defense' => 10, 'speed' => 20],
        ];

        $defenderUnits = [
            ['id' => 2, 'name' => 'Armored', 'hp' => 50, 'attack' => 5, 'defense' => 50, 'speed' => 10],
        ];

        $result = $this->resolver->resolveBattle($attackerUnits, $defenderUnits);

        // All damage values should be at least 1
        foreach ($result['turns'] as $turn) {
            $this->assertGreaterThanOrEqual(1, $turn['damage']);
        }
    }

    public function test_generate_dummy_team(): void
    {
        $dummyTeam = $this->resolver->generateDummyTeam(3, 'medium');

        $this->assertCount(3, $dummyTeam);
        $this->assertArrayHasKey('id', $dummyTeam[0]);
        $this->assertArrayHasKey('name', $dummyTeam[0]);
        $this->assertArrayHasKey('hp', $dummyTeam[0]);
        $this->assertArrayHasKey('attack', $dummyTeam[0]);
        $this->assertArrayHasKey('defense', $dummyTeam[0]);
        $this->assertArrayHasKey('speed', $dummyTeam[0]);
    }

    public function test_difficulty_affects_dummy_stats(): void
    {
        $easyTeam = $this->resolver->generateDummyTeam(1, 'easy');
        $hardTeam = $this->resolver->generateDummyTeam(1, 'hard');

        // Hard team should have higher stats
        $this->assertGreaterThan($easyTeam[0]['hp'], $hardTeam[0]['hp']);
        $this->assertGreaterThan($easyTeam[0]['attack'], $hardTeam[0]['attack']);
    }

    public function test_battle_with_real_team_models(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        $team = Team::factory()->create(['user_id' => $user->id]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'hp' => 100,
            'attack' => 25,
            'defense' => 15,
            'speed' => 20,
        ]);

        $team->units()->attach($unit->id, ['position' => 1]);

        $dummyTeam = $this->resolver->generateDummyTeam(1, 'easy');

        $result = $this->resolver->resolveBattle($team, $dummyTeam);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('winner', $result);
        $this->assertArrayHasKey('turns', $result);
        $this->assertArrayHasKey('total_turns', $result);
    }
}
