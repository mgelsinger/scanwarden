<?php

namespace App\Services;

use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\User;

class UnitSummoningService
{
    private array $nameTemplates = [
        'Food Sector' => ['Culinary', 'Gourmet', 'Savory', 'Sweet', 'Spicy', 'Bitter', 'Umami'],
        'Tech Sector' => ['Cyber', 'Digital', 'Quantum', 'Neural', 'Circuit', 'Data', 'Binary'],
        'Bio Sector' => ['Organic', 'Vital', 'Cellular', 'Genetic', 'Flora', 'Fauna', 'Molecular'],
        'Industrial Sector' => ['Iron', 'Steel', 'Forge', 'Mechanical', 'Engine', 'Gear', 'Construct'],
        'Arcane Sector' => ['Mystic', 'Ethereal', 'Rune', 'Spell', 'Magic', 'Shadow', 'Crystal'],
        'Household Sector' => ['Domestic', 'Common', 'Utility', 'Everyday', 'Practical', 'Essential', 'Handy'],
    ];

    private array $suffixes = [
        'Guardian', 'Sentinel', 'Warden', 'Protector', 'Champion', 'Knight', 'Warrior',
        'Keeper', 'Defender', 'Spirit', 'Entity', 'Being', 'Construct', 'Familiar'
    ];


    public function summonUnit(User $user, Sector $sector, string $upcSeed): SummonedUnit
    {
        // Use UPC as seed for deterministic randomness
        $seed = crc32($upcSeed . $user->id . time());
        srand($seed);

        // Determine rarity
        $rarity = $this->determineRarity();

        // Generate name
        $name = $this->generateName($sector);

        // Generate base stats
        $baseStats = $this->generateBaseStats();

        // Apply rarity multiplier
        $stats = $this->applyRarityMultiplier($baseStats, $rarity);

        $unit = SummonedUnit::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'name' => $name,
            'rarity' => $rarity,
            'tier' => 1,
            'evolution_level' => 0,
            'hp' => $stats['hp'],
            'attack' => $stats['attack'],
            'defense' => $stats['defense'],
            'speed' => $stats['speed'],
            'passive_ability' => $this->generatePassiveAbility($sector, $rarity),
        ]);

        // Reset random seed
        srand();

        return $unit;
    }

    public function determineRarity(): string
    {
        $rarities = config('rarities.tiers');
        $rand = rand(1, 100);

        $cumulativeProbability = 0;
        foreach ($rarities as $key => $rarity) {
            $cumulativeProbability += $rarity['probability'];
            if ($rand <= $cumulativeProbability) {
                return $key;
            }
        }

        return 'common'; // fallback
    }

    public function applyRarityMultiplier(array $baseStats, string $rarity): array
    {
        $multiplier = config("rarities.tiers.{$rarity}.stat_multiplier", 1.0);

        return [
            'hp' => (int) round($baseStats['hp'] * $multiplier),
            'attack' => (int) round($baseStats['attack'] * $multiplier),
            'defense' => (int) round($baseStats['defense'] * $multiplier),
            'speed' => (int) round($baseStats['speed'] * $multiplier),
        ];
    }

    private function generateName(Sector $sector): string
    {
        $templates = $this->nameTemplates[$sector->name] ?? ['Unknown'];
        $prefix = $templates[array_rand($templates)];
        $suffix = $this->suffixes[array_rand($this->suffixes)];

        return $prefix . ' ' . $suffix;
    }

    private function generateBaseStats(): array
    {
        return [
            'hp' => rand(80, 120),
            'attack' => rand(15, 25),
            'defense' => rand(10, 20),
            'speed' => rand(8, 15),
        ];
    }

    private function generatePassiveAbility(Sector $sector, string $rarity): ?string
    {
        $abilities = [
            'Food Sector' => 'Sustenance: Heals 5% HP at battle start',
            'Tech Sector' => 'Overclock: +10% speed when HP above 50%',
            'Bio Sector' => 'Regeneration: Heals 3 HP per turn',
            'Industrial Sector' => 'Reinforced: +15% defense',
            'Arcane Sector' => 'Mystic Strike: 20% chance to deal double damage',
            'Household Sector' => 'Reliable: Cannot be stunned or disabled',
        ];

        if ($rarity === 'common') {
            return null;
        }

        return $abilities[$sector->name] ?? null;
    }

    public function summonUnitWithRarity(User $user, Sector $sector, string $upcSeed, string $rarity): SummonedUnit
    {
        // Use UPC as seed for deterministic randomness
        $seed = crc32($upcSeed . $user->id . time());
        srand($seed);

        // Generate name
        $name = $this->generateName($sector);

        // Generate base stats
        $baseStats = $this->generateBaseStats();

        // Apply rarity multiplier
        $stats = $this->applyRarityMultiplier($baseStats, $rarity);

        $unit = SummonedUnit::create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'name' => $name,
            'rarity' => $rarity,
            'tier' => 1,
            'evolution_level' => 0,
            'hp' => $stats['hp'],
            'attack' => $stats['attack'],
            'defense' => $stats['defense'],
            'speed' => $stats['speed'],
            'passive_ability' => $this->generatePassiveAbility($sector, $rarity),
            'source' => 'transmutation',
        ]);

        // Reset random seed
        srand();

        return $unit;
    }

    public function getUnitSummary(SummonedUnit $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'rarity' => $unit->rarity,
            'tier' => $unit->tier,
            'stats' => [
                'hp' => $unit->hp,
                'attack' => $unit->attack,
                'defense' => $unit->defense,
                'speed' => $unit->speed,
            ],
            'passive_ability' => $unit->passive_ability,
        ];
    }
}
