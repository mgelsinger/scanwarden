<?php

namespace Database\Seeders;

use App\Models\EvolutionRule;
use Illuminate\Database\Seeder;

class EvolutionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'from_tier' => 1,
                'to_tier' => 2,
                'required_sector_energy' => 50,
                'required_generic_essence' => 10,
                'required_sector_essence' => 20,
                'hp_multiplier' => 1.3,
                'attack_multiplier' => 1.3,
                'defense_multiplier' => 1.2,
                'speed_multiplier' => 1.1,
                'new_name_suffix' => 'Elite',
                'new_trait' => 'Enhanced combat capabilities',
            ],
            [
                'from_tier' => 2,
                'to_tier' => 3,
                'required_sector_energy' => 150,
                'required_generic_essence' => 25,
                'required_sector_essence' => 50,
                'hp_multiplier' => 1.5,
                'attack_multiplier' => 1.5,
                'defense_multiplier' => 1.4,
                'speed_multiplier' => 1.2,
                'new_name_suffix' => 'Champion',
                'new_trait' => 'Master of its domain',
            ],
            [
                'from_tier' => 3,
                'to_tier' => 4,
                'required_sector_energy' => 300,
                'required_generic_essence' => 50,
                'required_sector_essence' => 100,
                'hp_multiplier' => 1.8,
                'attack_multiplier' => 1.7,
                'defense_multiplier' => 1.6,
                'speed_multiplier' => 1.3,
                'new_name_suffix' => 'Legend',
                'new_trait' => 'Legendary power from the sector',
            ],
        ];

        foreach ($rules as $rule) {
            EvolutionRule::firstOrCreate(
                [
                    'from_tier' => $rule['from_tier'],
                    'to_tier' => $rule['to_tier'],
                ],
                $rule
            );
        }
    }
}
