<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\SectorTower;
use App\Models\SectorTowerStage;
use Illuminate\Database\Seeder;

class SectorTowerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all sectors
        $sectors = Sector::all();

        foreach ($sectors as $sector) {
            // Create tower for each sector
            $tower = SectorTower::create([
                'sector_id' => $sector->id,
                'slug' => strtolower(str_replace(' ', '-', $sector->name)) . '-tower',
                'name' => $sector->name . ' Spire',
                'description' => 'Ascend the ' . $sector->name . ' Spire and prove your mastery over ' . strtolower($sector->name) . ' units.',
                'max_floor' => 5,
                'is_active' => true,
            ]);

            // Create 5 floors for each tower
            $this->createStages($tower, $sector);
        }
    }

    /**
     * Create stages for a tower.
     */
    private function createStages(SectorTower $tower, Sector $sector): void
    {
        // Base stats that scale with floor
        $baseHp = 60;
        $baseAttack = 25;
        $baseDefense = 15;
        $baseSpeed = 20;

        // Passive key for this sector
        $passiveKey = match ($sector->name) {
            'Tech Sector' => 'tech_overclock',
            'Bio Sector' => 'bio_regeneration',
            'Arcane Sector' => 'arcane_surge',
            default => null,
        };

        for ($floor = 1; $floor <= 5; $floor++) {
            // Scale stats by floor
            $multiplier = 1 + (($floor - 1) * 0.3);
            $rarity = match ($floor) {
                1 => 'common',
                2 => 'uncommon',
                3, 4 => 'rare',
                5 => 'epic',
                default => 'common',
            };

            // Create enemy team (1-3 units based on floor)
            $enemyCount = min($floor, 3);
            $enemyTeam = [];

            for ($i = 1; $i <= $enemyCount; $i++) {
                $enemyTeam[] = [
                    'slot' => $i,
                    'sector_id' => $sector->id,
                    'rarity' => $rarity,
                    'tier' => 1,
                    'base_hp' => (int) ($baseHp * $multiplier),
                    'base_attack' => (int) ($baseAttack * $multiplier),
                    'base_defense' => (int) ($baseDefense * $multiplier),
                    'base_speed' => (int) ($baseSpeed * $multiplier),
                    'passive_key' => $rarity !== 'common' ? $passiveKey : null,
                ];
            }

            // Create rewards based on floor
            $rewards = $this->createRewards($sector->id, $floor);

            // Create the stage
            SectorTowerStage::create([
                'tower_id' => $tower->id,
                'floor' => $floor,
                'enemy_team' => $enemyTeam,
                'recommended_power' => (int) (50 * $multiplier * $enemyCount),
                'rewards' => $rewards,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Create rewards array for a floor.
     */
    private function createRewards(int $sectorId, int $floor): array
    {
        $rewards = [];

        // Generic essence reward (scales with floor)
        $rewards[] = [
            'type' => 'essence',
            'essence_type' => 'generic',
            'amount' => 30 * $floor,
        ];

        // Sector energy reward (floors 2+)
        if ($floor >= 2) {
            $rewards[] = [
                'type' => 'sector_energy',
                'sector_id' => $sectorId,
                'amount' => 20 * $floor,
            ];
        }

        // Sector essence reward (floors 3+)
        if ($floor >= 3) {
            $rewards[] = [
                'type' => 'essence',
                'essence_type' => 'sector',
                'sector_id' => $sectorId,
                'amount' => 10 * ($floor - 2),
            ];
        }

        return $rewards;
    }
}
