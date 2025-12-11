<?php

namespace Tests\Unit\Services;

use App\Models\EvolutionRule;
use App\Models\Sector;
use App\Models\SectorEnergy;
use App\Models\SummonedUnit;
use App\Models\User;
use App\Models\UserEssence;
use App\Services\EvolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private EvolutionService $evolutionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evolutionService = app(EvolutionService::class);
    }

    /**
     * Test that getEvolutionRequirements returns null when no rule exists
     */
    public function test_get_evolution_requirements_returns_null_when_no_rule_available(): void
    {
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 999, // Tier with no evolution rule
        ]);

        $requirements = $this->evolutionService->getEvolutionRequirements($unit);

        $this->assertNull($requirements);
    }

    /**
     * Test canEvolve returns false when no rule exists
     */
    public function test_can_evolve_returns_false_when_no_rule_available(): void
    {
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 999,
        ]);

        $canEvolve = $this->evolutionService->canEvolve($unit, $user);

        $this->assertFalse($canEvolve);
    }

    /**
     * Test canEvolve returns false when user doesn't have enough resources
     */
    public function test_can_evolve_returns_false_when_not_enough_resources(): void
    {
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        // Create evolution rule
        $rule = EvolutionRule::create([
            'from_tier' => 5,
            'to_tier' => 6,
            'required_sector_energy' => 100,
            'required_generic_essence' => 50,
            'required_sector_essence' => 75,
            'hp_multiplier' => 1.5,
            'attack_multiplier' => 1.5,
            'defense_multiplier' => 1.5,
            'speed_multiplier' => 1.5,
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 5,
        ]);

        // Give user insufficient resources
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 10, // Need 50
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 20, // Need 75
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 30, // Need 100
        ]);

        $canEvolve = $this->evolutionService->canEvolve($unit, $user);

        $this->assertFalse($canEvolve);
    }

    /**
     * Test evolveUnit throws exception when requirements not met
     */
    public function test_evolve_unit_throws_exception_when_requirements_not_met(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unit cannot be evolved. Requirements not met.');

        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        EvolutionRule::create([
            'from_tier' => 10,
            'to_tier' => 11,
            'required_sector_energy' => 100,
            'required_generic_essence' => 50,
            'required_sector_essence' => 75,
            'hp_multiplier' => 1.5,
            'attack_multiplier' => 1.5,
            'defense_multiplier' => 1.5,
            'speed_multiplier' => 1.5,
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 10,
        ]);

        // Don't provide any resources

        $this->evolutionService->evolveUnit($unit, $user);
    }

    /**
     * Test successful evolution updates stats, tier, and deducts resources
     */
    public function test_successful_evolution_updates_stats_and_tier(): void
    {
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        // Create evolution rule
        $rule = EvolutionRule::create([
            'from_tier' => 15,
            'to_tier' => 16,
            'required_sector_energy' => 100,
            'required_generic_essence' => 50,
            'required_sector_essence' => 75,
            'hp_multiplier' => 1.5,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.4,
            'speed_multiplier' => 1.2,
            'new_name_suffix' => 'Elite',
            'new_trait' => 'Enhanced power',
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 15,
            'evolution_level' => 0,
            'hp' => 100,
            'attack' => 50,
            'defense' => 40,
            'speed' => 30,
            'name' => 'Test Guardian',
            'passive_ability' => null,
        ]);

        // Give user sufficient resources
        $genericEssence = UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        $sectorEssence = UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 150,
        ]);

        $sectorEnergy = SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 200,
        ]);

        // Evolve the unit
        $evolvedUnit = $this->evolutionService->evolveUnit($unit, $user);

        // Verify tier and evolution level updated
        $this->assertEquals(16, $evolvedUnit->tier);
        $this->assertEquals(1, $evolvedUnit->evolution_level);

        // Verify stats were multiplied correctly
        $this->assertEquals(150, $evolvedUnit->hp); // 100 * 1.5
        $this->assertEquals(65, $evolvedUnit->attack); // 50 * 1.3
        $this->assertEquals(56, $evolvedUnit->defense); // 40 * 1.4
        $this->assertEquals(36, $evolvedUnit->speed); // 30 * 1.2

        // Verify name was updated
        $this->assertEquals('Test Guardian Elite', $evolvedUnit->name);

        // Verify passive ability was added
        $this->assertEquals('Enhanced power', $evolvedUnit->passive_ability);

        // Verify resources were deducted
        $this->assertEquals(50, $genericEssence->fresh()->amount); // 100 - 50
        $this->assertEquals(75, $sectorEssence->fresh()->amount); // 150 - 75
        $this->assertEquals(100, $sectorEnergy->fresh()->current_energy); // 200 - 100
    }

    /**
     * Test canEvolve returns true when all requirements are met
     */
    public function test_can_evolve_returns_true_when_all_requirements_met(): void
    {
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        EvolutionRule::create([
            'from_tier' => 20,
            'to_tier' => 21,
            'required_sector_energy' => 50,
            'required_generic_essence' => 25,
            'required_sector_essence' => 30,
            'hp_multiplier' => 1.5,
            'attack_multiplier' => 1.5,
            'defense_multiplier' => 1.5,
            'speed_multiplier' => 1.5,
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 20,
        ]);

        // Give user sufficient resources
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 50,
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 60,
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $canEvolve = $this->evolutionService->canEvolve($unit, $user);

        $this->assertTrue($canEvolve);
    }

    /**
     * Test evolution works when only sector energy is required (no essence)
     */
    public function test_evolution_with_only_sector_energy_requirement(): void
    {
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        // Create evolution rule with no essence requirements
        EvolutionRule::create([
            'from_tier' => 25,
            'to_tier' => 26,
            'required_sector_energy' => 50,
            'required_generic_essence' => 0,
            'required_sector_essence' => 0,
            'hp_multiplier' => 1.2,
            'attack_multiplier' => 1.2,
            'defense_multiplier' => 1.2,
            'speed_multiplier' => 1.2,
        ]);

        $user = User::factory()->create();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 25,
            'hp' => 100,
            'attack' => 50,
            'defense' => 40,
            'speed' => 30,
        ]);

        // Give user only sector energy
        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $canEvolve = $this->evolutionService->canEvolve($unit, $user);
        $this->assertTrue($canEvolve);

        $evolvedUnit = $this->evolutionService->evolveUnit($unit, $user);
        $this->assertEquals(26, $evolvedUnit->tier);
    }
}

