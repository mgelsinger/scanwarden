<?php

namespace Tests\Unit\Services;

use App\Exceptions\CannotAffordTransmutationException;
use App\Models\Sector;
use App\Models\SectorEnergy;
use App\Models\TransmutationHistory;
use App\Models\TransmutationRecipe;
use App\Models\User;
use App\Models\UserEssence;
use App\Services\EssenceTransmuterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssenceTransmuterServiceTest extends TestCase
{
    use RefreshDatabase;

    private EssenceTransmuterService $transmuterService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transmuterService = app(EssenceTransmuterService::class);
    }

    /**
     * Test canAffordRecipe returns false when user has no resources
     */
    public function test_cannot_afford_recipe_when_no_resources(): void
    {
        $user = User::factory()->create();

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Test',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        $canAfford = $this->transmuterService->canAffordRecipe($user, $recipe);

        $this->assertFalse($canAfford);
    }

    /**
     * Test canAffordRecipe returns true when user has sufficient resources
     */
    public function test_can_afford_recipe_when_has_sufficient_resources(): void
    {
        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 200,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Test',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        $canAfford = $this->transmuterService->canAffordRecipe($user, $recipe);

        $this->assertTrue($canAfford);
    }

    /**
     * Test transmute throws exception when cannot afford
     */
    public function test_transmute_throws_exception_when_cannot_afford(): void
    {
        $this->expectException(CannotAffordTransmutationException::class);

        $user = User::factory()->create();

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Test',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        $this->transmuterService->transmute($user, $recipe);
    }

    /**
     * Test successful transmutation - generic essence to sector energy
     */
    public function test_successful_transmutation_essence_to_sector_energy(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 150,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Convert essence to energy',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
            'outputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        $result = $this->transmuterService->transmute($user, $recipe);

        // Assert result structure
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['inputs']);
        $this->assertCount(1, $result['outputs']);

        // Assert generic essence was deducted
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();
        $this->assertEquals(50, $essence->amount);

        // Assert sector energy was granted
        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertNotNull($energy);
        $this->assertEquals(50, $energy->current_energy);

        // Assert history was created
        $history = TransmutationHistory::where('user_id', $user->id)
            ->where('recipe_id', $recipe->id)
            ->first();
        $this->assertNotNull($history);
        $this->assertIsArray($history->inputs_consumed);
        $this->assertIsArray($history->outputs_received);
    }

    /**
     * Test successful transmutation - sector energy to unit summon
     */
    public function test_successful_transmutation_with_unit_summon(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 300,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Summon unit',
            'sector_id' => $sector->id,
            'required_inputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 200],
            ],
            'outputs' => [
                ['type' => 'unit_summon', 'sector_id' => $sector->id, 'rarity' => 'rare'],
            ],
            'is_active' => true,
        ]);

        $result = $this->transmuterService->transmute($user, $recipe);

        // Assert result structure
        $this->assertTrue($result['success']);

        // Assert sector energy was deducted
        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertEquals(100, $energy->current_energy);

        // Assert unit was created
        $this->assertDatabaseHas('summoned_units', [
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        // Assert history was created with unit in outputs
        $history = TransmutationHistory::where('user_id', $user->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('unit', $history->outputs_received[0]['type']);
        $this->assertArrayHasKey('unit', $history->outputs_received[0]);
    }

    /**
     * Test transmutation with multiple inputs
     */
    public function test_transmutation_with_multiple_inputs(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Multi-input recipe',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 50],
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'sector', 'sector_id' => $sector->id, 'amount' => 100],
            ],
            'is_active' => true,
        ]);

        $result = $this->transmuterService->transmute($user, $recipe);

        $this->assertTrue($result['success']);

        // Assert both inputs were consumed
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();
        $this->assertEquals(50, $essence->amount);

        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertEquals(50, $energy->current_energy);

        // Assert sector essence was granted
        $sectorEssence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertNotNull($sectorEssence);
        $this->assertEquals(100, $sectorEssence->amount);
    }

    /**
     * Test canAffordRecipe with sector energy requirement
     */
    public function test_can_afford_recipe_with_sector_energy(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 250,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Test',
            'required_inputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 200],
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        $this->assertTrue($this->transmuterService->canAffordRecipe($user, $recipe));
    }

    /**
     * Test canAffordRecipe returns false with insufficient sector energy
     */
    public function test_cannot_afford_recipe_with_insufficient_sector_energy(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        SectorEnergy::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'current_energy' => 100,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Test',
            'required_inputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 200],
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        $this->assertFalse($this->transmuterService->canAffordRecipe($user, $recipe));
    }
}
