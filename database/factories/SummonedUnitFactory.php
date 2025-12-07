<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SummonedUnit>
 */
class SummonedUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rarities = ['common', 'uncommon', 'rare', 'epic', 'legendary'];

        return [
            'user_id' => \App\Models\User::factory(),
            'sector_id' => 1, // Will use seeded sector or can be overridden
            'name' => fake()->words(2, true) . ' ' . fake()->randomElement(['Guardian', 'Sentinel', 'Warden']),
            'rarity' => fake()->randomElement($rarities),
            'tier' => fake()->numberBetween(1, 3),
            'evolution_level' => 0,
            'hp' => fake()->numberBetween(80, 150),
            'attack' => fake()->numberBetween(15, 30),
            'defense' => fake()->numberBetween(10, 25),
            'speed' => fake()->numberBetween(8, 20),
            'passive_ability' => fake()->boolean(30) ? fake()->sentence() : null,
            'source' => fake()->randomElement(['summon', 'starter', 'quest']),
        ];
    }
}
