<?php

namespace Tests\Unit\Services;

use App\Services\UnitSummoningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RarityDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rarity_distribution_is_reasonable(): void
    {
        $service = new UnitSummoningService();
        $results = [];

        // Simulate 1000 summons
        for ($i = 0; $i < 1000; $i++) {
            $rarity = $service->determineRarity();
            $results[$rarity] = ($results[$rarity] ?? 0) + 1;
        }

        // Common should be most frequent (around 50%)
        $this->assertGreaterThan(400, $results['common'] ?? 0);
        $this->assertLessThan(600, $results['common'] ?? 0);

        // Uncommon should be second most frequent (around 30%)
        $this->assertGreaterThan(200, $results['uncommon'] ?? 0);
        $this->assertLessThan(400, $results['uncommon'] ?? 0);

        // Legendary should be rarest (around 1%)
        $this->assertLessThan(50, $results['legendary'] ?? 0);
    }

    public function test_rarity_multiplier_increases_stats(): void
    {
        $service = new UnitSummoningService();
        $baseStats = ['hp' => 100, 'attack' => 50, 'defense' => 50, 'speed' => 50];

        $commonStats = $service->applyRarityMultiplier($baseStats, 'common');
        $legendaryStats = $service->applyRarityMultiplier($baseStats, 'legendary');

        // Common has 1.0x multiplier
        $this->assertEquals(100, $commonStats['hp']);
        $this->assertEquals(50, $commonStats['attack']);

        // Legendary has 2.2x multiplier
        $this->assertEquals(220, $legendaryStats['hp']);
        $this->assertEquals(110, $legendaryStats['attack']);
        $this->assertEquals(110, $legendaryStats['defense']);
        $this->assertEquals(110, $legendaryStats['speed']);

        // Legendary stats should always be higher than common
        $this->assertGreaterThan($commonStats['hp'], $legendaryStats['hp']);
        $this->assertGreaterThan($commonStats['attack'], $legendaryStats['attack']);
    }

    public function test_all_rarities_can_be_generated(): void
    {
        $service = new UnitSummoningService();
        $results = [];

        // Try to generate all rarities (may take many attempts)
        $maxAttempts = 10000;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $rarity = $service->determineRarity();
            $results[$rarity] = true;

            // Check if we've found all 5 rarities
            if (count($results) === 5) {
                break;
            }
        }

        // Assert all rarities can be generated
        $this->assertArrayHasKey('common', $results);
        $this->assertArrayHasKey('uncommon', $results);
        $this->assertArrayHasKey('rare', $results);
        $this->assertArrayHasKey('epic', $results);
        $this->assertArrayHasKey('legendary', $results);
    }

    public function test_stat_multipliers_are_progressive(): void
    {
        $service = new UnitSummoningService();
        $baseStats = ['hp' => 100, 'attack' => 50, 'defense' => 50, 'speed' => 50];

        $commonStats = $service->applyRarityMultiplier($baseStats, 'common');
        $uncommonStats = $service->applyRarityMultiplier($baseStats, 'uncommon');
        $rareStats = $service->applyRarityMultiplier($baseStats, 'rare');
        $epicStats = $service->applyRarityMultiplier($baseStats, 'epic');
        $legendaryStats = $service->applyRarityMultiplier($baseStats, 'legendary');

        // Each tier should have progressively higher stats
        $this->assertGreaterThan($commonStats['hp'], $uncommonStats['hp']);
        $this->assertGreaterThan($uncommonStats['hp'], $rareStats['hp']);
        $this->assertGreaterThan($rareStats['hp'], $epicStats['hp']);
        $this->assertGreaterThan($epicStats['hp'], $legendaryStats['hp']);
    }
}
