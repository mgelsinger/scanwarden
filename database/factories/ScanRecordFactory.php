<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScanRecord>
 */
class ScanRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'raw_upc' => $this->faker->numerify('############'),
            'sector_id' => 1,
            'rewards' => [
                'energy' => $this->faker->numberBetween(5, 15),
                'unit_summoned' => $this->faker->boolean(30),
            ],
        ];
    }
}
