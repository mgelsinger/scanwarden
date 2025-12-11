<?php

namespace Database\Seeders;

use App\Models\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quests = [
            // Daily Missions
            [
                'slug' => 'daily_scan_3',
                'name' => 'Daily Scanner',
                'description' => 'Complete 3 scans today',
                'type' => 'daily',
                'category' => 'scan',
                'target_value' => 3,
                'is_daily' => true,
                'is_repeatable' => true,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
                ],
                'reset_period' => 'daily',
            ],
            [
                'slug' => 'daily_pvp_win_1',
                'name' => 'Victory',
                'description' => 'Win 1 PvP battle today',
                'type' => 'daily',
                'category' => 'battle_pvp_win',
                'target_value' => 1,
                'is_daily' => true,
                'is_repeatable' => true,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
                    ['type' => 'sector_energy', 'sector_id' => 1, 'amount' => 20],
                ],
                'reset_period' => 'daily',
            ],
            [
                'slug' => 'daily_evolution_1',
                'name' => 'Evolution Master',
                'description' => 'Evolve 1 unit today',
                'type' => 'daily',
                'category' => 'evolution',
                'target_value' => 1,
                'is_daily' => true,
                'is_repeatable' => true,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 40],
                ],
                'reset_period' => 'daily',
            ],

            // Achievements
            [
                'slug' => 'achieve_100_scans',
                'name' => 'Scan Veteran',
                'description' => 'Complete 100 scans total',
                'type' => 'achievement',
                'category' => 'scan',
                'target_value' => 100,
                'is_daily' => false,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 200],
                ],
                'reset_period' => null,
            ],
            [
                'slug' => 'achieve_rating_1200',
                'name' => 'Skilled Battler',
                'description' => 'Reach 1200 rating',
                'type' => 'achievement',
                'category' => 'rating_threshold',
                'target_value' => 1200,
                'is_daily' => false,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
                ],
                'reset_period' => null,
            ],
            [
                'slug' => 'achieve_rating_1500',
                'name' => 'Expert Tactician',
                'description' => 'Reach 1500 rating',
                'type' => 'achievement',
                'category' => 'rating_threshold',
                'target_value' => 1500,
                'is_daily' => false,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 300],
                ],
                'reset_period' => null,
            ],
            [
                'slug' => 'achieve_10_evolutions',
                'name' => 'Evolution Enthusiast',
                'description' => 'Evolve 10 units total',
                'type' => 'achievement',
                'category' => 'evolution',
                'target_value' => 10,
                'is_daily' => false,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 150],
                ],
                'reset_period' => null,
            ],
            [
                'slug' => 'achieve_50_pvp_wins',
                'name' => 'PvP Champion',
                'description' => 'Win 50 PvP battles',
                'type' => 'achievement',
                'category' => 'battle_pvp_win',
                'target_value' => 50,
                'is_daily' => false,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 250],
                ],
                'reset_period' => null,
            ],
            [
                'slug' => 'achieve_10_transmutations',
                'name' => 'Transmutation Adept',
                'description' => 'Perform 10 transmutations',
                'type' => 'achievement',
                'category' => 'transmuter_use',
                'target_value' => 10,
                'is_daily' => false,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_payload' => [
                    ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 120],
                ],
                'reset_period' => null,
            ],
        ];

        foreach ($quests as $questData) {
            Quest::firstOrCreate(
                ['slug' => $questData['slug']],
                $questData
            );
        }
    }
}
