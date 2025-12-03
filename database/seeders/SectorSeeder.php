<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            [
                'name' => 'Food Sector',
                'description' => 'The realm of sustenance and nourishment, where units embody the essence of consumables and culinary creations.',
                'color' => '#FF6B35',
            ],
            [
                'name' => 'Tech Sector',
                'description' => 'The digital frontier, populated by entities born from electronics, gadgets, and cutting-edge innovation.',
                'color' => '#004E98',
            ],
            [
                'name' => 'Bio Sector',
                'description' => 'The living world, where organic compounds, medicines, and natural products give rise to powerful guardians.',
                'color' => '#2A9D8F',
            ],
            [
                'name' => 'Industrial Sector',
                'description' => 'The forge of civilization, drawing power from tools, materials, and manufactured goods.',
                'color' => '#6C757D',
            ],
            [
                'name' => 'Arcane Sector',
                'description' => 'The mysterious dimension, home to entities from books, games, and items of enigmatic origin.',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Household Sector',
                'description' => 'The domain of daily life, where common household items manifest as dependable allies.',
                'color' => '#F4A261',
            ],
        ];

        foreach ($sectors as $sector) {
            Sector::create($sector);
        }
    }
}
