<?php

namespace Tests\Unit\Services;

use App\Models\Sector;
use App\Models\User;
use App\Services\EssenceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssenceGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected EssenceGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EssenceGenerationService();
    }

    public function test_generates_essence_rewards_array(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, false);

        $this->assertIsArray($result);
    }

    public function test_returns_empty_array_when_no_essence_generated(): void
    {
        // Set 0% chance for all essence types
        config(['essence.generic.chance' => 0]);
        config(['essence.sector.chance' => 0]);
        config(['essence.summon_bonus.enabled' => false]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, false);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_generic_essence_is_generated_when_chance_is_100_percent(): void
    {
        config(['essence.generic.chance' => 1.0]);
        config(['essence.generic.min' => 10]);
        config(['essence.generic.max' => 10]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, false);

        $genericEssence = collect($result)->firstWhere('type', 'generic');

        $this->assertNotNull($genericEssence);
        $this->assertEquals('generic', $genericEssence['type']);
        $this->assertEquals(10, $genericEssence['amount']);
        $this->assertNull($genericEssence['sector_id']);
    }

    public function test_sector_essence_is_generated_when_chance_is_100_percent(): void
    {
        config(['essence.sector.chance' => 1.0]);
        config(['essence.sector.min' => 5]);
        config(['essence.sector.max' => 5]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, false);

        $sectorEssence = collect($result)->firstWhere('type', 'sector');

        $this->assertNotNull($sectorEssence);
        $this->assertEquals('sector', $sectorEssence['type']);
        $this->assertEquals(5, $sectorEssence['amount']);
        $this->assertEquals($sector->id, $sectorEssence['sector_id']);
        $this->assertEquals($sector->name, $sectorEssence['sector_name']);
    }

    public function test_summon_bonus_is_generated_when_unit_summoned(): void
    {
        config(['essence.summon_bonus.enabled' => true]);
        config(['essence.summon_bonus.min' => 20]);
        config(['essence.summon_bonus.max' => 20]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, true);

        $summonBonus = collect($result)->firstWhere('type', 'summon_bonus');

        $this->assertNotNull($summonBonus);
        $this->assertEquals('summon_bonus', $summonBonus['type']);
        $this->assertEquals(20, $summonBonus['amount']);
        $this->assertNull($summonBonus['sector_id']);
    }

    public function test_summon_bonus_not_generated_when_no_summon(): void
    {
        config(['essence.summon_bonus.enabled' => true]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, false);

        $summonBonus = collect($result)->firstWhere('type', 'summon_bonus');

        $this->assertNull($summonBonus);
    }

    public function test_summon_bonus_not_generated_when_disabled(): void
    {
        config(['essence.summon_bonus.enabled' => false]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, true);

        $summonBonus = collect($result)->firstWhere('type', 'summon_bonus');

        $this->assertNull($summonBonus);
    }

    public function test_essence_amounts_respect_min_max_configuration(): void
    {
        config(['essence.generic.chance' => 1.0]);
        config(['essence.generic.min' => 5]);
        config(['essence.generic.max' => 15]);

        $user = User::factory()->create();
        $sector = Sector::first();

        // Run multiple times to ensure amounts are within range
        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->generateEssenceForScan($user, $sector, false);
            $genericEssence = collect($result)->firstWhere('type', 'generic');

            $this->assertGreaterThanOrEqual(5, $genericEssence['amount']);
            $this->assertLessThanOrEqual(15, $genericEssence['amount']);
        }
    }

    public function test_multiple_essence_types_can_be_generated_simultaneously(): void
    {
        config(['essence.generic.chance' => 1.0]);
        config(['essence.generic.min' => 10]);
        config(['essence.generic.max' => 10]);
        config(['essence.sector.chance' => 1.0]);
        config(['essence.sector.min' => 5]);
        config(['essence.sector.max' => 5]);
        config(['essence.summon_bonus.enabled' => true]);
        config(['essence.summon_bonus.min' => 20]);
        config(['essence.summon_bonus.max' => 20]);

        $user = User::factory()->create();
        $sector = Sector::first();

        $result = $this->service->generateEssenceForScan($user, $sector, true);

        $this->assertCount(3, $result);

        $genericEssence = collect($result)->firstWhere('type', 'generic');
        $sectorEssence = collect($result)->firstWhere('type', 'sector');
        $summonBonus = collect($result)->firstWhere('type', 'summon_bonus');

        $this->assertNotNull($genericEssence);
        $this->assertNotNull($sectorEssence);
        $this->assertNotNull($summonBonus);
    }
}
