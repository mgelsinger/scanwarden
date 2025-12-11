<?php

namespace Tests\Unit\Services;

use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Services\Battle\Passives\PassiveEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassiveEngineTest extends TestCase
{
    use RefreshDatabase;

    private PassiveEngine $engine;
    private Sector $techSector;
    private Sector $bioSector;
    private Sector $arcaneSector;
    private Sector $foodSector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PassiveEngine();

        // Create sectors for testing
        $this->techSector = Sector::factory()->create(['name' => 'Tech Sector']);
        $this->bioSector = Sector::factory()->create(['name' => 'Bio Sector']);
        $this->arcaneSector = Sector::factory()->create(['name' => 'Arcane Sector']);
        $this->foodSector = Sector::factory()->create(['name' => 'Food Sector']);
    }

    /** @test */
    public function tech_sector_units_get_tech_overclock_passive()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->techSector->id,
            'rarity' => 'rare',
            'passive_key' => 'tech_overclock',
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertArrayHasKey('tech_overclock', $passives);
        $this->assertEquals('tech_overclock', $passives['tech_overclock']->getKey());
    }

    /** @test */
    public function bio_sector_units_get_bio_regeneration_passive()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->bioSector->id,
            'rarity' => 'rare',
            'passive_key' => 'bio_regeneration',
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertArrayHasKey('bio_regeneration', $passives);
        $this->assertEquals('bio_regeneration', $passives['bio_regeneration']->getKey());
    }

    /** @test */
    public function arcane_sector_units_get_arcane_surge_passive()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->arcaneSector->id,
            'rarity' => 'rare',
            'passive_key' => 'arcane_surge',
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertArrayHasKey('arcane_surge', $passives);
        $this->assertEquals('arcane_surge', $passives['arcane_surge']->getKey());
    }

    /** @test */
    public function common_units_get_no_passive()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->techSector->id,
            'rarity' => 'common',
            'passive_key' => null,
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertEmpty($passives);
    }

    /** @test */
    public function legendary_units_get_legendary_aura()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->techSector->id,
            'rarity' => 'legendary',
            'passive_key' => 'tech_overclock',
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertArrayHasKey('legendary_aura', $passives);
        $this->assertEquals('legendary_aura', $passives['legendary_aura']->getKey());
    }

    /** @test */
    public function legendary_units_get_both_sector_passive_and_legendary_aura()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->bioSector->id,
            'rarity' => 'legendary',
            'passive_key' => 'bio_regeneration',
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertArrayHasKey('bio_regeneration', $passives);
        $this->assertArrayHasKey('legendary_aura', $passives);
        $this->assertCount(2, $passives);
    }

    /** @test */
    public function sectors_without_default_passive_get_no_passive()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->foodSector->id,
            'rarity' => 'rare',
            'passive_key' => null,
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertEmpty($passives);
    }

    /** @test */
    public function passive_description_uses_config_data()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->techSector->id,
            'rarity' => 'rare',
            'passive_key' => 'tech_overclock',
        ]);
        $unit->load('sector');

        $description = $this->engine->getPassiveDescription($unit);

        $this->assertStringContainsString('Overclocked Systems', $description);
        $this->assertStringContainsString('First attack deals +20% damage', $description);
    }

    /** @test */
    public function legendary_passive_description_includes_both_passives()
    {
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->techSector->id,
            'rarity' => 'legendary',
            'passive_key' => 'tech_overclock',
        ]);
        $unit->load('sector');

        $description = $this->engine->getPassiveDescription($unit);

        // Should contain both tech_overclock and legendary_aura
        $this->assertStringContainsString('Overclocked Systems', $description);
        $this->assertStringContainsString('Mythic Presence', $description);
    }

    /** @test */
    public function respects_explicitly_set_passive_key()
    {
        // Create a Tech unit but give it Bio passive explicitly
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->techSector->id,
            'rarity' => 'rare',
            'passive_key' => 'bio_regeneration',
        ]);
        $unit->load('sector');

        $passives = $this->engine->resolvePassivesForUnit($unit);

        $this->assertArrayHasKey('bio_regeneration', $passives);
        $this->assertArrayNotHasKey('tech_overclock', $passives);
    }
}
