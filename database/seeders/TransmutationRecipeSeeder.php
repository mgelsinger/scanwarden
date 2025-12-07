<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\TransmutationRecipe;
use Illuminate\Database\Seeder;

class TransmutationRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = Sector::all();

        // Generic recipes
        TransmutationRecipe::create([
            'name' => 'Essence Consolidation',
            'description' => 'Convert 100 generic essence into 50 sector-specific essence',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100]
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'sector', 'amount' => 50, 'sector_id' => null]
            ],
            'is_active' => true,
            'level_requirement' => 1,
        ]);

        // Sector-specific summon recipes
        foreach ($sectors as $sector) {
            TransmutationRecipe::create([
                'name' => "Summon {$sector->name} Rare",
                'description' => "Guaranteed Rare unit from {$sector->name} sector",
                'sector_id' => $sector->id,
                'required_inputs' => [
                    ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 200]
                ],
                'outputs' => [
                    ['type' => 'unit_summon', 'sector_id' => $sector->id, 'rarity' => 'rare']
                ],
                'is_active' => true,
                'level_requirement' => 5,
            ]);

            TransmutationRecipe::create([
                'name' => "Summon {$sector->name} Epic",
                'description' => "Guaranteed Epic unit from {$sector->name} sector",
                'sector_id' => $sector->id,
                'required_inputs' => [
                    ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 500]
                ],
                'outputs' => [
                    ['type' => 'unit_summon', 'sector_id' => $sector->id, 'rarity' => 'epic']
                ],
                'is_active' => true,
                'level_requirement' => 10,
            ]);
        }
    }
}
