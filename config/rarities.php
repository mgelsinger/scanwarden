<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Unit Rarity Tiers
    |--------------------------------------------------------------------------
    |
    | Defines the rarity system for summoned units.
    | Each tier has a color, probability, and stat multiplier.
    |
    */

    'tiers' => [
        'common' => [
            'name' => 'Common',
            'color' => '#9CA3AF', // gray
            'probability' => 50, // out of 100
            'stat_multiplier' => 1.0,
        ],
        'uncommon' => [
            'name' => 'Uncommon',
            'color' => '#10B981', // green
            'probability' => 30,
            'stat_multiplier' => 1.2,
        ],
        'rare' => [
            'name' => 'Rare',
            'color' => '#3B82F6', // blue
            'probability' => 15,
            'stat_multiplier' => 1.5,
        ],
        'epic' => [
            'name' => 'Epic',
            'color' => '#8B5CF6', // purple
            'probability' => 4,
            'stat_multiplier' => 1.8,
        ],
        'legendary' => [
            'name' => 'Legendary',
            'color' => '#F59E0B', // amber/gold
            'probability' => 1,
            'stat_multiplier' => 2.2,
        ],
    ],
];
