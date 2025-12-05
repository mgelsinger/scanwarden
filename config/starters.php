<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Starter Unit Templates
    |--------------------------------------------------------------------------
    |
    | These are the starter units that new players can choose from.
    | Each starter represents a different sector and playstyle.
    |
    */

    'templates' => [
        [
            'key' => 'aegis_guardian',
            'name' => 'Aegis Guardian',
            'sector_slug' => 'industrial',
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 120,
            'attack' => 40,
            'defense' => 50,
            'speed' => 30,
            'passive_ability' => 'Iron Wall: Reduces incoming damage by 10%',
            'description' => 'A steadfast defender from the Industrial Sector. High HP and defense make it perfect for players who prefer tanky playstyles.',
            'lore' => 'Forged in the heart of forgotten factories, the Aegis Guardian stands as the last line of defense.',
        ],
        [
            'key' => 'spark_striker',
            'name' => 'Spark Striker',
            'sector_slug' => 'tech',
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 80,
            'attack' => 55,
            'defense' => 30,
            'speed' => 60,
            'passive_ability' => 'Quick Strike: +15% damage when attacking first',
            'description' => 'A lightning-fast Tech Sector unit. Excels at dealing quick, decisive blows with superior speed.',
            'lore' => 'Born from corrupted data streams, Spark Striker harnesses digital energy to strike with precision.',
        ],
        [
            'key' => 'verdant_healer',
            'name' => 'Verdant Healer',
            'sector_slug' => 'bio',
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 100,
            'attack' => 35,
            'defense' => 40,
            'speed' => 45,
            'passive_ability' => 'Regeneration: Restores 5% HP per turn',
            'description' => 'A Bio Sector support unit with natural healing abilities. Provides sustain for longer battles.',
            'lore' => 'Cultivated in ancient bioengineering labs, the Verdant Healer carries the essence of life itself.',
        ],
        [
            'key' => 'nova_striker',
            'name' => 'Nova Striker',
            'sector_slug' => 'food',
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 90,
            'attack' => 50,
            'defense' => 35,
            'speed' => 50,
            'passive_ability' => 'Balanced Force: +10% to all stats when team HP is above 50%',
            'description' => 'A balanced Food Sector fighter. No major weaknesses, adapts to any team composition.',
            'lore' => 'Infused with the essence of forgotten recipes, Nova Striker brings sustenance and strength.',
        ],
        [
            'key' => 'mystic_sage',
            'name' => 'Mystic Sage',
            'sector_slug' => 'arcane',
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 70,
            'attack' => 60,
            'defense' => 25,
            'speed' => 55,
            'passive_ability' => 'Arcane Power: +20% attack but -10% defense',
            'description' => 'An Arcane Sector glass cannon. Deals massive damage but requires careful positioning.',
            'lore' => 'Summoned from the void between realities, the Mystic Sage wields forbidden knowledge.',
        ],
        [
            'key' => 'hearth_defender',
            'name' => 'Hearth Defender',
            'sector_slug' => 'household',
            'rarity' => 'rare',
            'tier' => 1,
            'hp' => 110,
            'attack' => 40,
            'defense' => 45,
            'speed' => 35,
            'passive_ability' => 'Home Guard: +15% defense when protecting allies',
            'description' => 'A Household Sector protector. Specializes in team support and defensive tactics.',
            'lore' => 'Guardian of hearth and home, this stalwart defender never yields.',
        ],
    ],
];
