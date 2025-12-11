<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\SectorEnergy;
use App\Models\SummonedUnit;
use App\Models\TransmutationRecipe;
use App\Models\User;
use App\Models\UserEssence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransmuterFeatureTest extends TestCase
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

        $sector = Sector::firstOrCreate(
            ['slug' => 'test-sector-' . $counter],
            [
                'name' => 'Test Sector ' . $counter,
                'description' => 'Test sector for unit tests',
                'color' => '#000000',
            ]
        );

        SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        return $user;
    }

    /**
     * Test authenticated user can access transmuter index
     */
    public function test_authenticated_user_can_access_transmuter_index(): void
    {
        $user = $this->createUserWithStarter();

        $response = $this->actingAs($user)->get(route('transmuter.index'));

        $response->assertStatus(200);
        $response->assertViewIs('transmuter.index');
        $response->assertViewHas(['recipes', 'userEssence']);
    }

    /**
     * Test guest cannot access transmuter index
     */
    public function test_guest_cannot_access_transmuter_index(): void
    {
        $response = $this->get(route('transmuter.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test transmuter index shows recipes with affordability indicators
     */
    public function test_transmuter_index_shows_recipes_with_affordability(): void
    {
        $user = $this->createUserWithStarter();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test-transmuter',
            'description' => 'Test',
            'color' => '#FF0000',
        ]);

        // Give user some generic essence
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        // Create an affordable recipe
        $affordableRecipe = TransmutationRecipe::create([
            'name' => 'Affordable Recipe',
            'description' => 'User can afford this',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
            'outputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 25],
            ],
            'is_active' => true,
        ]);

        // Create an unaffordable recipe
        $unaffordableRecipe = TransmutationRecipe::create([
            'name' => 'Expensive Recipe',
            'description' => 'User cannot afford this',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 500],
            ],
            'outputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 250],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('transmuter.index'));

        $response->assertStatus(200);
        $response->assertSee('Affordable Recipe');
        $response->assertSee('Expensive Recipe');

        // Check that recipes have affordability flag
        $recipes = $response->viewData('recipes');
        $affordable = $recipes->firstWhere('id', $affordableRecipe->id);
        $expensive = $recipes->firstWhere('id', $unaffordableRecipe->id);

        $this->assertTrue($affordable->can_afford);
        $this->assertFalse($expensive->can_afford);
    }

    /**
     * Test successful transmutation via POST
     */
    public function test_successful_transmutation_via_post(): void
    {
        $user = $this->createUserWithStarter();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test-transmuter-success',
            'description' => 'Test',
            'color' => '#00FF00',
        ]);

        // Give user generic essence
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 200,
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

        $response = $this->actingAs($user)->post(route('transmuter.transmute', $recipe), [
            'confirm' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Transmutation successful!');
        $response->assertSessionHas('transmutation_result');

        // Verify essence was deducted
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();
        $this->assertEquals(100, $essence->amount);

        // Verify sector energy was granted
        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertNotNull($energy);
        $this->assertEquals(50, $energy->current_energy);

        // Verify transmutation history was recorded
        $this->assertDatabaseHas('transmutation_history', [
            'user_id' => $user->id,
            'recipe_id' => $recipe->id,
        ]);
    }

    /**
     * Test unsuccessful transmutation (not enough resources)
     */
    public function test_unsuccessful_transmutation_insufficient_resources(): void
    {
        $user = $this->createUserWithStarter();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test-transmuter-fail',
            'description' => 'Test',
            'color' => '#0000FF',
        ]);

        // Give user insufficient generic essence
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 50,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Test Recipe',
            'description' => 'Requires more essence than user has',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 200],
            ],
            'outputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 100],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('transmuter.transmute', $recipe), [
            'confirm' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You do not have enough essence or energy for this recipe.');

        // Verify essence was NOT deducted
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();
        $this->assertEquals(50, $essence->amount);

        // Verify no sector energy was created
        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertNull($energy);

        // Verify no transmutation history was recorded
        $this->assertDatabaseMissing('transmutation_history', [
            'user_id' => $user->id,
            'recipe_id' => $recipe->id,
        ]);
    }

    /**
     * Test inactive recipe cannot be used
     */
    public function test_inactive_recipe_cannot_be_used(): void
    {
        $user = $this->createUserWithStarter();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test-inactive',
            'description' => 'Test',
            'color' => '#FFFF00',
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 500,
        ]);

        $recipe = TransmutationRecipe::create([
            'name' => 'Inactive Recipe',
            'description' => 'This recipe is disabled',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
            'outputs' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 50],
            ],
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->post(route('transmuter.transmute', $recipe), [
            'confirm' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This recipe is not available.');

        // Verify essence was NOT deducted
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();
        $this->assertEquals(500, $essence->amount);
    }

    /**
     * Test transmutation requires confirmation
     */
    public function test_transmutation_requires_confirmation(): void
    {
        $user = $this->createUserWithStarter();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test-confirm',
            'description' => 'Test',
            'color' => '#00FFFF',
        ]);

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
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 50],
            ],
            'is_active' => true,
        ]);

        // Try without confirm field
        $response = $this->actingAs($user)->post(route('transmuter.transmute', $recipe), []);

        $response->assertSessionHasErrors('confirm');

        // Verify essence was NOT deducted
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();
        $this->assertEquals(200, $essence->amount);
    }

    /**
     * Test transmuter index shows user's essence balances
     */
    public function test_transmuter_index_shows_user_essence_balances(): void
    {
        $user = $this->createUserWithStarter();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test-balances',
            'description' => 'Test',
            'color' => '#FF00FF',
        ]);

        // Create multiple essence types
        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 150,
        ]);

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 75,
        ]);

        $response = $this->actingAs($user)->get(route('transmuter.index'));

        $response->assertStatus(200);

        // Check that userEssence is passed to view
        $userEssence = $response->viewData('userEssence');
        $this->assertCount(2, $userEssence);

        // Verify balances
        $generic = $userEssence->where('type', 'generic')->first();
        $sectorEssence = $userEssence->where('type', 'sector')->first();

        $this->assertEquals(150, $generic->amount);
        $this->assertEquals(75, $sectorEssence->amount);
    }
}
