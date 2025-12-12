<?php

namespace Tests\Feature;

use App\Models\EvolutionRule;
use App\Models\Sector;
use App\Models\SectorEnergy;
use App\Models\SummonedUnit;
use App\Models\User;
use App\Models\UserEssence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitEvolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    /**
     * Helper to create a user with a starter unit (to bypass middleware)
     */
    private function createUserWithStarter(array $attributes = []): User
    {
        static $counter = 0;
        $counter++;

        $user = User::factory()->create($attributes);
        $sector = Sector::create([
            'name' => 'Test Sector ' . $counter . '-' . uniqid(),
            'slug' => 'test-' . $counter . '-' . uniqid(),
            'description' => 'Test sector',
            'color' => '#000000',
        ]);
        SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);
        return $user;
    }

    /**
     * Test that evolution UI is visible on unit detail page
     */
    public function test_evolution_ui_visible_on_unit_page(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Tech Sector ' . uniqid(),
            'slug' => 'tech-' . uniqid(),
            'description' => 'Tech sector',
            'color' => '#004E98',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
        ]);

        // Create evolution rule
        EvolutionRule::firstOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 50,
            'required_generic_essence' => 10,
            'required_sector_essence' => 20,
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
            ]
        );

        $this->actingAs($user);

        $response = $this->get(route('units.show', $unit));

        $response->assertStatus(200);
        $response->assertSee('Evolution');
        $response->assertSee('Requirements');
    }

    /**
     * Test that authenticated user cannot evolve another user's unit
     */
    public function test_cannot_evolve_foreign_unit(): void
    {
        $ownerUser = $this->createUserWithStarter();
        $otherUser = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Bio Sector ' . uniqid(),
            'slug' => 'bio-' . uniqid(),
            'description' => 'Bio sector',
            'color' => '#2A9D8F',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $ownerUser->id,
            'sector_id' => $sector->id,
            'tier' => 1,
        ]);

        // Create evolution rule
        EvolutionRule::firstOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 50,
            'required_generic_essence' => 10,
            'required_sector_essence' => 20,
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
            ]
        );

        // Give other user resources
        UserEssence::create([
            'user_id' => $otherUser->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        UserEssence::create([
            'user_id' => $otherUser->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 100,
        ]);

        SectorEnergy::create([
            'user_id' => $otherUser->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        // Try to evolve as other user
        $this->actingAs($otherUser);

        $response = $this->post(route('units.evolve', $unit));

        $response->assertStatus(403);
    }

    /**
     * Test successful evolution from UI with sufficient resources
     */
    public function test_successful_evolution_from_ui(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Food Sector ' . uniqid(),
            'slug' => 'food-' . uniqid(),
            'description' => 'Food sector',
            'color' => '#FF6B35',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
            'evolution_level' => 0,
            'hp' => 100,
            'attack' => 50,
            'defense' => 40,
            'speed' => 30,
            'name' => 'Culinary Guardian',
        ]);

        // Create evolution rule
        EvolutionRule::firstOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 50,
            'required_generic_essence' => 10,
            'required_sector_essence' => 20,
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
            'speed_multiplier' => 1.1,
            'new_name_suffix' => 'Elite',
        ]);

        // Give user sufficient resources
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 100,
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('units.evolve', $unit));

        $response->assertRedirect(route('units.show', $unit));
        $response->assertSessionHas('status');

        // Verify unit was evolved
        $unit->refresh();
        $this->assertEquals(2, $unit->tier);
        $this->assertEquals(1, $unit->evolution_level);
        $this->assertEquals(130, $unit->hp); // 100 * 1.3
        $this->assertEquals(65, $unit->attack); // 50 * 1.3
        $this->assertEquals(48, $unit->defense); // 40 * 1.2
        $this->assertEquals(33, $unit->speed); // 30 * 1.1
    }

    /**
     * Test evolution fails with insufficient generic essence
     */
    public function test_evolution_fails_with_insufficient_generic_essence(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Industrial Sector ' . uniqid(),
            'slug' => 'industrial-' . uniqid(),
            'description' => 'Industrial sector',
            'color' => '#6C757D',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
        ]);

        // Create evolution rule
        EvolutionRule::updateOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 50,
            'required_generic_essence' => 100, // Needs 100
            'required_sector_essence' => 20,
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
            ]
        );

        // Give user insufficient generic essence but enough of other resources
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 50, // Only has 50
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 100,
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('units.evolve', $unit));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify unit was NOT evolved
        $unit->refresh();
        $this->assertEquals(1, $unit->tier);
    }

    /**
     * Test evolution fails with insufficient sector essence
     */
    public function test_evolution_fails_with_insufficient_sector_essence(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Arcane Sector ' . uniqid(),
            'slug' => 'arcane-' . uniqid(),
            'description' => 'Arcane sector',
            'color' => '#8B5CF6',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
        ]);

        // Create evolution rule
        EvolutionRule::updateOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 50,
            'required_generic_essence' => 10,
            'required_sector_essence' => 100, // Needs 100
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
            ]
        );

        // Give user enough generic essence and energy but insufficient sector essence
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 50, // Only has 50
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('units.evolve', $unit));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify unit was NOT evolved
        $unit->refresh();
        $this->assertEquals(1, $unit->tier);
    }

    /**
     * Test evolution fails with insufficient sector energy
     */
    public function test_evolution_fails_with_insufficient_sector_energy(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Household Sector ' . uniqid(),
            'slug' => 'household-' . uniqid(),
            'description' => 'Household sector',
            'color' => '#F4A261',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
        ]);

        // Create evolution rule
        EvolutionRule::updateOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 100, // Needs 100
            'required_generic_essence' => 10,
            'required_sector_essence' => 20,
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
            ]
        );

        // Give user enough essence but insufficient energy
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 100,
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 50, // Only has 50
        ]);

        $this->actingAs($user);

        $response = $this->post(route('units.evolve', $unit));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify unit was NOT evolved
        $unit->refresh();
        $this->assertEquals(1, $unit->tier);
    }

    /**
     * Test viewing own unit page shows evolution information
     */
    public function test_viewing_own_unit_page_shows_evolution_info(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Test Sector ' . uniqid(),
            'slug' => 'test-' . uniqid(),
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
        ]);

        // Create evolution rule
        EvolutionRule::firstOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
            'required_sector_energy' => 50,
            'required_generic_essence' => 10,
            'required_sector_essence' => 20,
            'hp_multiplier' => 1.3,
            'attack_multiplier' => 1.3,
            'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
            ]
        );

        $this->actingAs($user);

        $response = $this->get(route('units.show', $unit));

        $response->assertStatus(200);
        $response->assertSee('Current Tier');
        $response->assertSee('Next Tier');
        $response->assertSee('Generic Essence');
        $response->assertSee('Essence'); // Will match both Generic and Sector essence
        $response->assertSee('Energy');
    }

    /**
     * Test unit at max tier shows appropriate message
     */
    public function test_unit_at_max_tier_shows_appropriate_message(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Test Sector ' . uniqid(),
            'slug' => 'test-' . uniqid(),
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 999, // No rule exists for this tier
        ]);

        $this->actingAs($user);

        $response = $this->get(route('units.show', $unit));

        $response->assertStatus(200);
        $response->assertSee('Max tier reached');
        $response->assertSee('cannot evolve further');
    }

    /**
     * Test multiple consecutive evolutions
     */
    public function test_multiple_consecutive_evolutions(): void
    {
        $user = $this->createUserWithStarter();

        $sector = Sector::create([
            'name' => 'Test Sector ' . uniqid(),
            'slug' => 'test-' . uniqid(),
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'tier' => 1,
            'evolution_level' => 0,
            'hp' => 100,
        ]);

        // Create evolution rules for multiple tiers
        EvolutionRule::updateOrCreate(
            ['from_tier' => 1, 'to_tier' => 2],
            [
                'required_sector_energy' => 50,
                'required_generic_essence' => 10,
                'required_sector_essence' => 20,
                'hp_multiplier' => 1.5,
                'attack_multiplier' => 1.5,
                'defense_multiplier' => 1.5,
                'speed_multiplier' => 1.5,
            ]
        );

        EvolutionRule::updateOrCreate(
            ['from_tier' => 2, 'to_tier' => 3],
            [
            'required_sector_energy' => 100,
            'required_generic_essence' => 20,
            'required_sector_essence' => 40,
            'hp_multiplier' => 1.5,
            'attack_multiplier' => 1.5,
            'defense_multiplier' => 1.5,
                'speed_multiplier' => 1.5,
            ]
        );

        // Give user plenty of resources
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 1000,
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 1000,
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 1000,
        ]);

        $this->actingAs($user);

        // First evolution: Tier 1 -> 2
        $this->post(route('units.evolve', $unit));
        $unit->refresh();
        $this->assertEquals(2, $unit->tier);
        $this->assertEquals(1, $unit->evolution_level);
        $this->assertEquals(150, $unit->hp); // 100 * 1.5

        // Second evolution: Tier 2 -> 3
        $this->post(route('units.evolve', $unit));
        $unit->refresh();
        $this->assertEquals(3, $unit->tier);
        $this->assertEquals(2, $unit->evolution_level);
        $this->assertEquals(225, $unit->hp); // 150 * 1.5
    }
}
