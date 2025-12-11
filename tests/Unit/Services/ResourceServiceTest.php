<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientResourcesException;
use App\Models\Sector;
use App\Models\SectorEnergy;
use App\Models\User;
use App\Models\UserEssence;
use App\Services\ResourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResourceService $resourceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceService = new ResourceService();
    }

    /**
     * Test granting generic essence to a user with no essence
     */
    public function test_grant_generic_essence_creates_new_record(): void
    {
        $user = User::factory()->create();

        $this->resourceService->grantGenericEssence($user, 100);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $this->assertNotNull($essence);
        $this->assertEquals(100, $essence->amount);
    }

    /**
     * Test granting generic essence to a user with existing essence
     */
    public function test_grant_generic_essence_increments_existing_record(): void
    {
        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 50,
        ]);

        $this->resourceService->grantGenericEssence($user, 100);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $this->assertNotNull($essence);
        $this->assertEquals(150, $essence->amount);
    }

    /**
     * Test granting sector essence
     */
    public function test_grant_sector_essence(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $this->resourceService->grantSectorEssence($user, $sector->id, 75);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sector->id)
            ->first();

        $this->assertNotNull($essence);
        $this->assertEquals(75, $essence->amount);
    }

    /**
     * Test granting sector energy
     */
    public function test_grant_sector_energy(): void
    {
        $user = User::factory()->create();
        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $this->resourceService->grantSectorEnergy($user, $sector->id, 50);

        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();

        $this->assertNotNull($energy);
        $this->assertEquals(50, $energy->current_energy);
    }

    /**
     * Test deducting generic essence with sufficient resources
     */
    public function test_deduct_generic_essence_with_sufficient_resources(): void
    {
        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        $this->resourceService->deductGenericEssence($user, 30);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $this->assertEquals(70, $essence->amount);
    }

    /**
     * Test deducting generic essence with insufficient resources throws exception
     */
    public function test_deduct_generic_essence_with_insufficient_resources_throws_exception(): void
    {
        $this->expectException(InsufficientResourcesException::class);

        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 10,
        ]);

        $this->resourceService->deductGenericEssence($user, 50);
    }

    /**
     * Test deducting generic essence with insufficient resources does not change balance
     */
    public function test_deduct_generic_essence_with_insufficient_resources_leaves_balance_unchanged(): void
    {
        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 10,
        ]);

        try {
            $this->resourceService->deductGenericEssence($user, 50);
        } catch (InsufficientResourcesException $e) {
            // Expected
        }

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $this->assertEquals(10, $essence->amount);
    }

    /**
     * Test deducting sector essence with sufficient resources
     */
    public function test_deduct_sector_essence_with_sufficient_resources(): void
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
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 100,
        ]);

        $this->resourceService->deductSectorEssence($user, $sector->id, 40);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sector->id)
            ->first();

        $this->assertEquals(60, $essence->amount);
    }

    /**
     * Test deducting sector energy with sufficient resources
     */
    public function test_deduct_sector_energy_with_sufficient_resources(): void
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
            'current_energy' => 200,
        ]);

        $this->resourceService->deductSectorEnergy($user, $sector->id, 50);

        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();

        $this->assertEquals(150, $energy->current_energy);
    }

    /**
     * Test userHasGenericEssence returns true when sufficient
     */
    public function test_user_has_generic_essence_returns_true_when_sufficient(): void
    {
        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        $this->assertTrue($this->resourceService->userHasGenericEssence($user, 50));
        $this->assertTrue($this->resourceService->userHasGenericEssence($user, 100));
    }

    /**
     * Test userHasGenericEssence returns false when insufficient
     */
    public function test_user_has_generic_essence_returns_false_when_insufficient(): void
    {
        $user = User::factory()->create();

        UserEssence::create([
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
            'amount' => 100,
        ]);

        $this->assertFalse($this->resourceService->userHasGenericEssence($user, 150));
    }

    /**
     * Test userHasGenericEssence returns false when no essence exists
     */
    public function test_user_has_generic_essence_returns_false_when_no_essence(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->resourceService->userHasGenericEssence($user, 50));
    }

    /**
     * Test userHasSectorEssence returns true when sufficient
     */
    public function test_user_has_sector_essence_returns_true_when_sufficient(): void
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
            'type' => 'sector',
            'sector_id' => $sector->id,
            'amount' => 80,
        ]);

        $this->assertTrue($this->resourceService->userHasSectorEssence($user, $sector->id, 50));
        $this->assertTrue($this->resourceService->userHasSectorEssence($user, $sector->id, 80));
    }

    /**
     * Test userHasSectorEnergy returns true when sufficient
     */
    public function test_user_has_sector_energy_returns_true_when_sufficient(): void
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
            'current_energy' => 150,
        ]);

        $this->assertTrue($this->resourceService->userHasSectorEnergy($user, $sector->id, 100));
        $this->assertTrue($this->resourceService->userHasSectorEnergy($user, $sector->id, 150));
    }

    /**
     * Test userHasSectorEnergy returns false when insufficient
     */
    public function test_user_has_sector_energy_returns_false_when_insufficient(): void
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
            'current_energy' => 150,
        ]);

        $this->assertFalse($this->resourceService->userHasSectorEnergy($user, $sector->id, 200));
    }
}
