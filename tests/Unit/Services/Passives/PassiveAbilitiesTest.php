<?php

namespace Tests\Unit\Services\Passives;

use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Services\Battle\Passives\ArcaneSurge;
use App\Services\Battle\Passives\BioRegeneration;
use App\Services\Battle\Passives\LegendaryAura;
use App\Services\Battle\Passives\TechOverclock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassiveAbilitiesTest extends TestCase
{
    use RefreshDatabase;

    private Sector $sector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sector = Sector::factory()->create(['name' => 'Tech Sector']);
    }

    /** @test */
    public function tech_overclock_applies_damage_boost_on_first_attack()
    {
        $passive = new TechOverclock();
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->sector->id,
            'rarity' => 'rare',
        ]);

        $battleState = [
            'passive_state' => [],
            'units' => [
                'attacker_1' => ['stats' => []],
            ],
        ];

        // Trigger battle start
        $passive->onBattleStart($battleState, 'attacker_1');

        // Check that first attack flag is set
        $this->assertTrue($battleState['passive_state']['attacker_1']['tech_overclock_first_attack']);

        // Trigger beforeUnitActs - should apply multiplier
        $passive->beforeUnitActs($battleState, 'attacker_1');

        // Check multiplier is applied
        $this->assertEquals(1.20, $battleState['units']['attacker_1']['stats']['damage_out_multiplier']);

        // Check flag is consumed
        $this->assertFalse($battleState['passive_state']['attacker_1']['tech_overclock_first_attack']);

        // Trigger afterUnitActs - should reset multiplier
        $passive->afterUnitActs($battleState, 'attacker_1');

        // Check multiplier is reset
        $this->assertEquals(1.0, $battleState['units']['attacker_1']['stats']['damage_out_multiplier']);
    }

    /** @test */
    public function tech_overclock_does_not_boost_second_attack()
    {
        $passive = new TechOverclock();

        $battleState = [
            'passive_state' => [
                'attacker_1' => ['tech_overclock_first_attack' => false],
            ],
            'units' => [
                'attacker_1' => ['stats' => []],
            ],
        ];

        // Trigger beforeUnitActs on second attack
        $passive->beforeUnitActs($battleState, 'attacker_1');

        // No multiplier should be applied
        $this->assertArrayNotHasKey('damage_out_multiplier', $battleState['units']['attacker_1']['stats']);
    }

    /** @test */
    public function bio_regeneration_heals_10_percent_max_hp()
    {
        $passive = new BioRegeneration();

        $battleState = [
            'all_units' => [
                [
                    'id' => 1,
                    'team' => 'attacker',
                    'hp' => 50,
                    'max_hp' => 100,
                ],
            ],
            'attacker_units' => [],
            'defender_units' => [],
        ];

        // Trigger afterUnitActs
        $passive->afterUnitActs($battleState, 'attacker_1');

        // HP should increase by 10 (10% of 100)
        $this->assertEquals(60, $battleState['all_units'][0]['hp']);
        $this->assertEquals(10, $battleState['all_units'][0]['last_heal']);
    }

    /** @test */
    public function bio_regeneration_caps_at_max_hp()
    {
        $passive = new BioRegeneration();

        $battleState = [
            'all_units' => [
                [
                    'id' => 1,
                    'team' => 'attacker',
                    'hp' => 95,
                    'max_hp' => 100,
                ],
            ],
            'attacker_units' => [],
            'defender_units' => [],
        ];

        // Trigger afterUnitActs
        $passive->afterUnitActs($battleState, 'attacker_1');

        // HP should be capped at max_hp
        $this->assertEquals(100, $battleState['all_units'][0]['hp']);
        $this->assertEquals(5, $battleState['all_units'][0]['last_heal']);
    }

    /** @test */
    public function bio_regeneration_does_not_heal_dead_units()
    {
        $passive = new BioRegeneration();

        $battleState = [
            'all_units' => [
                [
                    'id' => 1,
                    'team' => 'attacker',
                    'hp' => 0,
                    'max_hp' => 100,
                ],
            ],
            'attacker_units' => [],
            'defender_units' => [],
        ];

        // Trigger afterUnitActs
        $passive->afterUnitActs($battleState, 'attacker_1');

        // HP should remain 0
        $this->assertEquals(0, $battleState['all_units'][0]['hp']);
        $this->assertArrayNotHasKey('last_heal', $battleState['all_units'][0]);
    }

    /** @test */
    public function arcane_surge_applies_speed_boost_for_first_3_turns()
    {
        $passive = new ArcaneSurge();

        $battleState = [
            'all_units' => [
                [
                    'id' => 1,
                    'team' => 'attacker',
                    'speed' => 10,
                ],
            ],
            'attacker_units' => [],
            'defender_units' => [],
            'passive_state' => [],
        ];

        // Trigger battle start
        $passive->onBattleStart($battleState, 'attacker_1');

        // Speed should be boosted by 5
        $this->assertEquals(15, $battleState['all_units'][0]['speed']);
        $this->assertEquals(10, $battleState['all_units'][0]['original_speed']);
        $this->assertEquals(3, $battleState['passive_state']['attacker_1']['arcane_surge_turns_remaining']);

        // First turn
        $passive->afterUnitActs($battleState, 'attacker_1');
        $this->assertEquals(2, $battleState['passive_state']['attacker_1']['arcane_surge_turns_remaining']);
        $this->assertEquals(15, $battleState['all_units'][0]['speed']);

        // Second turn
        $passive->afterUnitActs($battleState, 'attacker_1');
        $this->assertEquals(1, $battleState['passive_state']['attacker_1']['arcane_surge_turns_remaining']);
        $this->assertEquals(15, $battleState['all_units'][0]['speed']);

        // Third turn - boost should expire
        $passive->afterUnitActs($battleState, 'attacker_1');
        $this->assertEquals(0, $battleState['passive_state']['attacker_1']['arcane_surge_turns_remaining']);
        $this->assertEquals(10, $battleState['all_units'][0]['speed']);
    }

    /** @test */
    public function legendary_aura_applies_damage_multipliers()
    {
        $passive = new LegendaryAura();
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->sector->id,
            'rarity' => 'legendary',
        ]);

        $this->assertTrue($passive->appliesTo($unit));

        $battleState = [
            'units' => [
                'attacker_1' => ['stats' => []],
            ],
        ];

        // Trigger battle start
        $passive->onBattleStart($battleState, 'attacker_1');

        // Check multipliers are set
        $this->assertEquals(1.10, $battleState['units']['attacker_1']['stats']['damage_out_multiplier']);
        $this->assertEquals(0.90, $battleState['units']['attacker_1']['stats']['damage_in_multiplier']);
    }

    /** @test */
    public function legendary_aura_stacks_with_existing_multipliers()
    {
        $passive = new LegendaryAura();
        $unit = SummonedUnit::factory()->create([
            'sector_id' => $this->sector->id,
            'rarity' => 'legendary',
        ]);

        $battleState = [
            'units' => [
                'attacker_1' => [
                    'stats' => [
                        'damage_out_multiplier' => 1.20,
                        'damage_in_multiplier' => 0.80,
                    ],
                ],
            ],
        ];

        // Trigger battle start
        $passive->onBattleStart($battleState, 'attacker_1');

        // Check multipliers stack correctly
        $this->assertEquals(1.32, $battleState['units']['attacker_1']['stats']['damage_out_multiplier']);
        $this->assertEquals(0.72, $battleState['units']['attacker_1']['stats']['damage_in_multiplier']);
    }

    /** @test */
    public function legendary_aura_only_applies_to_legendary_units()
    {
        $passive = new LegendaryAura();

        $rareUnit = SummonedUnit::factory()->create([
            'sector_id' => $this->sector->id,
            'rarity' => 'rare',
        ]);

        $legendaryUnit = SummonedUnit::factory()->create([
            'sector_id' => $this->sector->id,
            'rarity' => 'legendary',
        ]);

        $this->assertFalse($passive->appliesTo($rareUnit));
        $this->assertTrue($passive->appliesTo($legendaryUnit));
    }
}
