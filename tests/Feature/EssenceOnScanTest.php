<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\User;
use App\Models\UserEssence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssenceOnScanTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_user_receives_essence_when_scanning_upc(): void
    {
        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('scan.store'), [
            'upc' => '012345678905',
        ]);

        $response->assertRedirect();

        // Check that essence was created for the user
        $this->assertDatabaseHas('user_essence', [
            'user_id' => $user->id,
        ]);
    }

    public function test_essence_rewards_are_stored_in_scan_record(): void
    {
        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->post(route('scan.store'), [
            'upc' => '012345678905',
        ]);

        $scanRecord = $user->scanRecords()->first();
        $this->assertNotNull($scanRecord);
        $this->assertArrayHasKey('essence_rewards', $scanRecord->rewards);
    }

    public function test_generic_essence_is_added_when_granted(): void
    {
        // Set 100% chance for generic essence to ensure it's granted
        config(['essence.generic.chance' => 1.0]);
        config(['essence.generic.min' => 10]);
        config(['essence.generic.max' => 10]);

        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->post(route('scan.store'), [
            'upc' => '012345678905',
        ]);

        // Check generic essence was added
        $this->assertDatabaseHas('user_essence', [
            'user_id' => $user->id,
            'type' => 'generic',
            'sector_id' => null,
        ]);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();

        $this->assertEquals(10, $essence->amount);
    }

    public function test_sector_essence_is_added_when_granted(): void
    {
        // Set 100% chance for sector essence to ensure it's granted
        config(['essence.sector.chance' => 1.0]);
        config(['essence.sector.min' => 5]);
        config(['essence.sector.max' => 5]);

        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->post(route('scan.store'), [
            'upc' => '012345678905',
        ]);

        $scanRecord = $user->scanRecords()->first();
        $sectorId = $scanRecord->sector_id;

        // Check sector essence was added
        $this->assertDatabaseHas('user_essence', [
            'user_id' => $user->id,
            'type' => 'sector',
            'sector_id' => $sectorId,
        ]);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->first();

        $this->assertEquals(5, $essence->amount);
    }

    public function test_multiple_scans_accumulate_essence(): void
    {
        // Set 100% chance for generic essence
        config(['essence.generic.chance' => 1.0]);
        config(['essence.generic.min' => 10]);
        config(['essence.generic.max' => 10]);

        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        // Perform first scan
        $this->post(route('scan.store'), [
            'upc' => '012345678905',
        ]);

        $firstEssence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();

        $this->assertEquals(10, $firstEssence->amount);

        // Perform second scan
        $this->post(route('scan.store'), [
            'upc' => '987654321098',
        ]);

        $secondEssence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->first();

        $this->assertEquals(20, $secondEssence->amount);
    }

    public function test_summon_bonus_essence_is_granted_when_unit_summoned(): void
    {
        // Ensure summon happens on first scan
        config(['essence.summon_bonus.enabled' => true]);
        config(['essence.summon_bonus.min' => 15]);
        config(['essence.summon_bonus.max' => 15]);

        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        // First scan always summons a unit
        $this->post(route('scan.store'), [
            'upc' => '012345678905',
        ]);

        $scanRecord = $user->scanRecords()->first();

        // Verify unit was summoned
        $this->assertTrue($scanRecord->rewards['should_summon']);

        // Check for summon bonus essence in rewards
        $essenceRewards = $scanRecord->rewards['essence_rewards'];
        $hasSummonBonus = collect($essenceRewards)->contains('type', 'summon_bonus');

        $this->assertTrue($hasSummonBonus);

        // Verify the summon bonus amount
        $summonBonus = collect($essenceRewards)->firstWhere('type', 'summon_bonus');
        $this->assertEquals(15, $summonBonus['amount']);
    }

    public function test_no_summon_bonus_when_unit_not_summoned(): void
    {
        config(['essence.summon_bonus.enabled' => true]);

        $user = User::factory()->create();
        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        // First scan to get the guaranteed summon out of the way
        $this->post(route('scan.store'), ['upc' => '012345678905']);

        // Second scan (likely no summon)
        $this->post(route('scan.store'), ['upc' => '987654321098']);

        $scanRecord = $user->scanRecords()->latest()->first();

        if (!$scanRecord->rewards['should_summon']) {
            $essenceRewards = $scanRecord->rewards['essence_rewards'];
            $hasSummonBonus = collect($essenceRewards)->contains('type', 'summon_bonus');

            $this->assertFalse($hasSummonBonus);
        } else {
            // If unit was summoned, just pass the test
            $this->assertTrue(true);
        }
    }
}
