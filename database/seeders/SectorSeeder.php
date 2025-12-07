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
                'slug' => 'food',
                'description' => 'The realm of sustenance and nourishment, where units embody the essence of consumables and culinary creations.',
                'color' => '#FF6B35',
            ],
            [
                'name' => 'Tech Sector',
                'slug' => 'tech',
                'description' => 'The digital frontier, populated by entities born from electronics, gadgets, and cutting-edge innovation.',
                'color' => '#004E98',
            ],
            [
                'name' => 'Bio Sector',
                'slug' => 'bio',
                'description' => 'The living world, where organic compounds, medicines, and natural products give rise to powerful guardians.',
                'color' => '#2A9D8F',
            ],
            [
                'name' => 'Industrial Sector',
                'slug' => 'industrial',
                'description' => 'The forge of civilization, drawing power from tools, materials, and manufactured goods.',
                'color' => '#6C757D',
            ],
            [
                'name' => 'Arcane Sector',
                'slug' => 'arcane',
                'description' => 'The mysterious dimension, home to entities from books, games, and items of enigmatic origin.',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Household Sector',
                'slug' => 'household',
                'description' => 'The domain of daily life, where common household items manifest as dependable allies.',
                'color' => '#F4A261',
            ],
        ];

        foreach ($sectors as $sector) {
            Sector::firstOrCreate(
                ['name' => $sector['name']],
                $sector
            );
        }
    }
}
